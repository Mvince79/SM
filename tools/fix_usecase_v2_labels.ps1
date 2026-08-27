$root = 'D:\FPT_EDU\nam_2026\Study\DATN\repo\SM'
$path = Join-Path $root 'TimTre_UseCase_Updated.drawio'
[xml]$doc = Get-Content -Raw -LiteralPath $path
$cells = @($doc.mxfile.diagram.mxGraphModel.root.mxCell)

@{
    foundEdit   = "Xem / sửa / xoá`nhồ sơ mình tạo"
    foundNotify = "Nhận thông báo khi AI`ntìm thấy khớp"
    chatbot     = "Trò chuyện với`nChatbot trợ lý"
}.GetEnumerator() | ForEach-Object {
    $cellId = $_.Key
    $newValue = $_.Value
    $cell = @($cells | Where-Object { $_.id -eq $cellId })[0]
    if (!$cell) { throw "Không tìm thấy use case: $cellId" }
    $cell.value = $newValue
}

# Verify the independent KYC includes required by prompt v2.
foreach ($pair in @(@('u10','u16x'), @('found','u16x'))) {
    if (!($cells | Where-Object { $_.edge -eq '1' -and $_.source -eq $pair[0] -and $_.target -eq $pair[1] -and $_.style -like '*dashed=1*' })) {
        throw "Thiếu quan hệ include KYC: $($pair[0]) -> $($pair[1])"
    }
}

$settings = New-Object System.Xml.XmlWriterSettings
$settings.Indent = $true; $settings.Encoding = [System.Text.UTF8Encoding]::new($false)
$writer = [System.Xml.XmlWriter]::Create($path, $settings); $doc.Save($writer); $writer.Close()
