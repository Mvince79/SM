param([Parameter(Mandatory = $true)][string]$Path)

[xml]$doc = Get-Content -Raw -LiteralPath $Path
$root = $doc.mxfile.diagram.mxGraphModel.root
foreach ($edge in @($root.mxCell | Where-Object { $_.edge -eq '1' })) {
    if (!$edge.mxGeometry) {
        $g = $doc.CreateElement('mxGeometry')
        $g.SetAttribute('relative','1')
        $g.SetAttribute('as','geometry')
        [void]$edge.AppendChild($g)
    } else {
        $edge.mxGeometry.relative = '1'
        $edge.mxGeometry.as = 'geometry'
    }
}

$settings = New-Object System.Xml.XmlWriterSettings
$settings.Indent = $true; $settings.Encoding = [System.Text.UTF8Encoding]::new($false)
$writer = [System.Xml.XmlWriter]::Create($Path, $settings); $doc.Save($writer); $writer.Close()
