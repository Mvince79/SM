$root = 'D:\FPT_EDU\nam_2026\Study\DATN\repo\SM'
$path = Join-Path $root 'TimTre_UseCase_Updated.drawio'
[xml]$doc = Get-Content -Raw -LiteralPath $path
$xmlRoot = $doc.mxfile.diagram.mxGraphModel.root

function Remove-Cell([string]$id) {
    $n = @($xmlRoot.mxCell | Where-Object { $_.id -eq $id })[0]
    if ($n) { [void]$xmlRoot.RemoveChild($n) }
}
function Add-Vertex([string]$id, [string]$value, [string]$style, [string]$parent, [int]$x, [int]$y, [int]$w, [int]$h) {
    $c = $doc.CreateElement('mxCell'); $c.SetAttribute('id',$id); $c.SetAttribute('value',$value); $c.SetAttribute('style',$style); $c.SetAttribute('vertex','1'); $c.SetAttribute('parent',$parent)
    $g = $doc.CreateElement('mxGeometry'); $g.SetAttribute('x',$x); $g.SetAttribute('y',$y); $g.SetAttribute('width',$w); $g.SetAttribute('height',$h); $g.SetAttribute('as','geometry'); [void]$c.AppendChild($g); [void]$xmlRoot.AppendChild($c)
}
function Add-Edge([string]$id, [string]$value, [string]$source, [string]$target, [string]$style) {
    $c = $doc.CreateElement('mxCell'); $c.SetAttribute('id',$id); if ($value) { $c.SetAttribute('value',$value) }; $c.SetAttribute('edge','1'); $c.SetAttribute('parent','1'); $c.SetAttribute('source',$source); $c.SetAttribute('target',$target); $c.SetAttribute('style',$style); [void]$xmlRoot.AppendChild($c)
}

# Remove prohibited / duplicate functions from v2.
foreach ($id in 'u14','u14x','e13','i9','a3q','q7') { Remove-Cell $id }

$userStyle = 'ellipse;whiteSpace=wrap;html=1;strokeColor=#9CB9E8;'
$userNewStyle = 'ellipse;whiteSpace=wrap;html=1;strokeColor=#9CB9E8;strokeWidth=2;'
$include = 'dashed=1;dashPattern=3 3;endArrow=open;html=1;strokeColor=#D94841;fontColor=#D94841;'
$extend = $include
$plain = 'endArrow=none;html=1;strokeColor=#666666;'

# Add missing found_children core flow and public chatbot.
Add-Vertex 'found' 'Đăng tin tìm thấy trẻ' $userNewStyle 'uFrame' 150 1795 270 58
Add-Vertex 'foundEdit' 'Xem / sửa / xoá&#xa;hồ sơ mình tạo' $userStyle 'uFrame' 520 1775 250 68
Add-Vertex 'foundNotify' 'Nhận thông báo khi AI&#xa;tìm thấy khớp' $userStyle 'uFrame' 520 1865 250 68
Add-Vertex 'chatbot' 'Trò chuyện với&#xa;Chatbot trợ lý' $userNewStyle 'uFrame' 150 1895 270 68

Add-Edge 'e20' '' 'user' 'found' $plain
Add-Edge 'e21' '' 'user' 'chatbot' $plain
Add-Edge 'v2a' '&lt;&lt;extend&gt;&gt;' 'foundEdit' 'found' $extend
Add-Edge 'v2b' '&lt;&lt;extend&gt;&gt;' 'foundNotify' 'found' $extend
Add-Edge 'v2c' '&lt;&lt;include&gt;&gt;' 'u10' 'u16x' $include
Add-Edge 'v2d' '&lt;&lt;include&gt;&gt;' 'found' 'u16x' $include
Add-Edge 'v2e' '&lt;&lt;include&gt;&gt;' 'photo3' 'u8' $include

$settings = New-Object System.Xml.XmlWriterSettings
$settings.Indent = $true; $settings.Encoding = [System.Text.UTF8Encoding]::new($false)
$writer = [System.Xml.XmlWriter]::Create($path, $settings); $doc.Save($writer); $writer.Close()
