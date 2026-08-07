<?php

namespace App\Filament\Admin\Resources\Articles\Schemas;

use App\Models\User;
use App\Rules\SecureImage;
use App\Support\SecureUploadFilename;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nội dung bài viết')
                    ->schema([
                        TextInput::make('title')
                            ->label('Tiêu đề')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                $set('slug', Str::slug($state ?? ''));
                            }),

                        TextInput::make('slug')
                            ->label('Đường dẫn')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        RichEditor::make('content')
                            ->label('Nội dung')
                            ->required()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('articles/content')
                            ->fileAttachmentsVisibility('public')
                            ->fileAttachmentsAcceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->fileAttachmentsMaxSize(5120)
                            ->saveUploadedFileAttachmentUsing(function (TemporaryUploadedFile $file): string {
                                Validator::validate(
                                    ['file' => $file],
                                    ['file' => ['file', 'max:5120', new SecureImage]],
                                );

                                $path = $file->storeAs(
                                    'articles/content',
                                    SecureUploadFilename::for($file),
                                    'public',
                                );

                                if (! is_string($path)) {
                                    throw new \RuntimeException('Could not store the article attachment.');
                                }

                                return $path;
                            })
                            ->columnSpanFull(),

                        FileUpload::make('cover_image_path')
                            ->label('Ảnh bìa')
                            ->image()
                            ->disk('public')
                            ->directory('articles/covers')
                            ->imageEditor()
                            ->rules([new SecureImage])
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => SecureUploadFilename::for($file),
                            )
                            ->maxSize(5120),

                        Select::make('author_id')
                            ->label('Tác giả')
                            ->options(
                                User::query()
                                    ->orderBy('full_name')
                                    ->pluck('full_name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->default(fn (): ?int => Auth::id())
                            ->required(),

                        DateTimePicker::make('published_at')
                            ->label('Thời gian xuất bản')
                            ->helperText('Để trống hoặc chọn giờ tương lai nếu muốn lưu nháp, chưa công khai.')
                            ->default(now())
                            ->seconds(false),
                    ])
                    ->columns(2),

                Section::make('SEO')
                    ->description('Thông tin hiển thị trên công cụ tìm kiếm')
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('SEO title')
                            ->maxLength(60)
                            ->live(),

                        Textarea::make('meta_description')
                            ->label('SEO description')
                            ->rows(3)
                            ->maxLength(160)
                            ->live(),

                        TextEntry::make('seo_preview')
                            ->hiddenLabel()
                            ->label('Xem trước kết quả Google')
                            ->state(function (callable $get): HtmlString {
                                $title = $get('meta_title')
                                    ?: $get('title')
                                    ?: 'Tiêu đề bài viết';

                                $slug = $get('slug') ?: 'duong-dan-bai-viet';

                                $description = $get('meta_description')
                                    ?: 'Mô tả bài viết sẽ xuất hiện tại đây khi người dùng tìm kiếm trên Google.';

                                $url = url('/bai-viet/'.$slug);

                                return new HtmlString(
                                    '<div style="
                                        max-width: 650px;
                                        padding: 16px;
                                        border: 1px solid #e5e7eb;
                                        border-radius: 8px;
                                        background: #ffffff;
                                    ">
                                        <div style="
                                            color: #202124;
                                            font-size: 14px;
                                            margin-bottom: 4px;
                                            word-break: break-all;
                                        ">'
                                    .e($url).
                                    '</div>

                                        <div style="
                                            color: #1a0dab;
                                            font-size: 20px;
                                            line-height: 1.3;
                                            margin-bottom: 4px;
                                        ">'
                                    .e(Str::limit($title, 60, '...')).
                                    '</div>

                                        <div style="
                                            color: #4d5156;
                                            font-size: 14px;
                                            line-height: 1.5;
                                        ">'
                                    .e(Str::limit($description, 160, '...')).
                                    '</div>
                                    </div>'
                                );
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
