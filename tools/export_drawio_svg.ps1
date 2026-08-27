param(
    [Parameter(Mandatory = $true)][string]$InputPath,
    [Parameter(Mandatory = $true)][string]$OutputPath
)

[xml]$doc = Get-Content -Raw -LiteralPath $InputPath
$model = $doc.mxfile.diagram.mxGraphModel
$cells = @($model.root.mxCell)
$byId = @{}
foreach ($cell in $cells) { $byId[$cell.id] = $cell }

function E([string]$s) { [System.Security.SecurityElement]::Escape($s) }
function Label([string]$s, [double]$x, [double]$y, [double]$w, [double]$h, [int]$size = 15) {
    $lines = ($s -replace '&lt;', '<' -replace '&gt;', '>' -split "`n")
    $lineHeight = $size + 3
    $start = $y + ($h / 2) - (($lines.Count - 1) * $lineHeight / 2) + ($size * 0.36)
    $out = "<text x='$($x + $w / 2)' y='$start' text-anchor='middle' font-family='Arial, sans-serif' font-size='$size' fill='#182230'>"
    for ($i = 0; $i -lt $lines.Count; $i++) { $out += "<tspan x='$($x + $w / 2)' dy='$($(if ($i -eq 0) { 0 } else { $lineHeight }))'>$(E $lines[$i])</tspan>" }
    return "$out</text>"
}
function Box($cell) {
    $g = $cell.mxGeometry
    $x = [double]$g.x; $y = [double]$g.y
    $p = $cell.parent
    while ($p -and $byId.ContainsKey($p) -and $p -ne '1') {
        $parent = $byId[$p]
        if ($parent.mxGeometry) { $x += [double]$parent.mxGeometry.x; $y += [double]$parent.mxGeometry.y }
        $p = $parent.parent
    }
    [pscustomobject]@{ X=$x; Y=$y; W=[double]$g.width; H=[double]$g.height }
}

$svg = @("<svg xmlns='http://www.w3.org/2000/svg' width='$($model.pageWidth)' height='$($model.pageHeight)' viewBox='0 0 $($model.pageWidth) $($model.pageHeight)'>",
    "<defs><marker id='openRed' markerWidth='10' markerHeight='10' refX='9' refY='5' orient='auto'><path d='M 9 1 L 1 5 L 9 9' fill='none' stroke='#D94841' stroke-width='1.5'/></marker></defs>",
    "<rect width='100%' height='100%' fill='white'/>")

# Frames first
foreach ($cell in $cells | Where-Object { $_.vertex -eq '1' -and $_.style -like '*swimlane*' }) {
    $b = Box $cell
    $svg += "<rect x='$($b.X)' y='$($b.Y)' width='$($b.W)' height='$($b.H)' fill='none' stroke='#666' stroke-width='2'/>"
    $svg += "<line x1='$($b.X)' y1='$($b.Y + 32)' x2='$($b.X + $b.W)' y2='$($b.Y + 32)' stroke='#666' stroke-width='2'/>"
    $svg += "<text x='$($b.X + 12)' y='$($b.Y + 22)' font-family='Arial, sans-serif' font-weight='bold' font-size='17'>$(E $cell.value)</text>"
}

# Edges, behind shapes
foreach ($cell in $cells | Where-Object { $_.edge -eq '1' }) {
    if (!$byId.ContainsKey($cell.source) -or !$byId.ContainsKey($cell.target)) { continue }
    $s = Box $byId[$cell.source]; $t = Box $byId[$cell.target]
    $x1 = $s.X + $s.W / 2; $y1 = $s.Y + $s.H / 2; $x2 = $t.X + $t.W / 2; $y2 = $t.Y + $t.H / 2
    $red = $cell.style -like '*#D94841*'; $color = if ($red) { '#D94841' } else { '#666666' }
    $dash = if ($cell.style -like '*dashed=1*') { " stroke-dasharray='7 6'" } else { '' }
    $arrow = if ($red) { " marker-end='url(#openRed)'" } else { '' }
    $svg += "<line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='$color' stroke-width='1.5'$dash$arrow/>"
    if ($cell.value) { $svg += "<text x='$(($x1+$x2)/2)' y='$(($y1+$y2)/2-5)' text-anchor='middle' font-family='Arial, sans-serif' font-size='12' fill='$color'>$(E ($cell.value -replace '&lt;', '<' -replace '&gt;', '>'))</text>" }
}

# Actors and use cases
foreach ($cell in $cells | Where-Object { $_.vertex -eq '1' -and $_.style -notlike '*swimlane*' }) {
    $b = Box $cell
    if ($cell.style -like '*umlActor*') {
        $cx = $b.X + $b.W / 2; $top = $b.Y + 5
        $svg += "<circle cx='$cx' cy='$($top+14)' r='11' fill='white' stroke='#555' stroke-width='2'/><line x1='$cx' y1='$($top+25)' x2='$cx' y2='$($top+65)' stroke='#555' stroke-width='2'/><line x1='$($cx-18)' y1='$($top+39)' x2='$($cx+18)' y2='$($top+39)' stroke='#555' stroke-width='2'/><line x1='$cx' y1='$($top+65)' x2='$($cx-18)' y2='$($top+92)' stroke='#555' stroke-width='2'/><line x1='$cx' y1='$($top+65)' x2='$($cx+18)' y2='$($top+92)' stroke='#555' stroke-width='2'/>"
        $svg += Label $cell.value $b.X ($b.Y + 96) $b.W 30 13
    } else {
        $stroke = if ($cell.style -like '*#777777*') { '#777777' } else { '#9CB9E8' }
        $width = if ($cell.style -like '*strokeWidth=2*') { '2.5' } else { '1.5' }
        $svg += "<ellipse cx='$($b.X + $b.W/2)' cy='$($b.Y + $b.H/2)' rx='$($b.W/2)' ry='$($b.H/2)' fill='white' stroke='$stroke' stroke-width='$width'/>"
        $svg += Label $cell.value $b.X $b.Y $b.W $b.H 14
    }
}
$svg += '</svg>'
[System.IO.File]::WriteAllText($OutputPath, ($svg -join "`n"), [System.Text.UTF8Encoding]::new($false))
