$root = 'D:\FPT_EDU\nam_2026\Study\DATN\repo\SM'
$source = Join-Path $root 'TimTre_UseCase_Updated.drawio'
$target = Join-Path $root 'TimTre_UseCase_Updated_Import.drawio'
[xml]$doc = Get-Content -Raw -LiteralPath $source
$model = $doc.mxfile.diagram.mxGraphModel
$cells = @($model.root.mxCell)
$frames = @{}
foreach ($id in 'uFrame','aFrame') {
    $frame = @($cells | Where-Object { $_.id -eq $id })[0]
    $frames[$id] = @{ x = [double]$frame.mxGeometry.x; y = [double]$frame.mxGeometry.y }
}

# Draw.io imports reliably when all shapes live directly beneath layer 1.
foreach ($cell in $cells | Where-Object { $_.vertex -eq '1' -and $frames.ContainsKey($_.parent) }) {
    $offset = $frames[$cell.parent]
    $cell.mxGeometry.x = ([double]$cell.mxGeometry.x + $offset.x).ToString([Globalization.CultureInfo]::InvariantCulture)
    $cell.mxGeometry.y = ([double]$cell.mxGeometry.y + $offset.y).ToString([Globalization.CultureInfo]::InvariantCulture)
    $cell.parent = '1'
}

# Let diagrams.net fit the content on opening instead of retaining a stale viewport.
$model.dx = '0'; $model.dy = '0'; $model.page = '0'
$settings = New-Object System.Xml.XmlWriterSettings
$settings.Indent = $true; $settings.Encoding = [System.Text.UTF8Encoding]::new($false)
$writer = [System.Xml.XmlWriter]::Create($target, $settings); $doc.Save($writer); $writer.Close()
