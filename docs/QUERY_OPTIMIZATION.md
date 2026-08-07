# Query Optimization - C-02

## /missing (trang danh sách công khai)
- Trước: 93 queries (Controller gọi getListForPublic() thừa, 
  trong khi Livewire CasesGrid đã tự gọi lại toàn bộ dữ liệu)
- Sau: 6 queries - xóa lệnh gọi Service thừa trong 
  MissingController@index, chỉ trả về view trống cho Livewire 
  tự quản lý.
- Giảm 93%.