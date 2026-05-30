<h2>Hồ sơ bị từ chối</h2>

<p>Xin chào {{ $missingChild->contact_name }},</p>

<p>
    Hồ sơ <strong>{{ $missingChild->full_name }}</strong>
    ({{ $missingChild->case_code }})
    chưa được phê duyệt.
</p>

<p>
    Lý do từ chối:
</p>

<p>
    <strong>{{ $missingChild->rejection_reason }}</strong>
</p>

<p>
    Vui lòng cập nhật lại hồ sơ và gửi lại nếu cần.
</p>

<p>
    Trân trọng,<br>
    TìmTrẻ.vn
</p>