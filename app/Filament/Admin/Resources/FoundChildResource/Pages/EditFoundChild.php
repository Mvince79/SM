<?php

namespace App\Filament\Admin\Resources\FoundChildResource\Pages;

use App\Filament\Admin\Resources\FoundChildResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

use App\Models\Photo;

class EditFoundChild extends EditRecord
{
    protected static string $resource = FoundChildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $state = $this->form->getState();
        if (!empty($state['photo_upload'])) {
            // unset existing primary
            Photo::where('model_type', \App\Models\FoundChild::class)
                ->where('model_id', $this->record->id)
                ->where('is_primary', 1)
                ->update(['is_primary' => 0]);

            $this->persistUploadedPhotos((array) $state['photo_upload']);
        }
    }

    private function persistUploadedPhotos(array $paths): void
    {
        $disk = Storage::disk('public');
        $targetDirectory = 'found_children/' . $this->record->id . '/original';
        $first = true;

        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $fileName = basename($path);
            $finalPath = str_starts_with($path, $targetDirectory . '/') ? $path : $targetDirectory . '/' . $fileName;

            if ($path !== $finalPath && $disk->exists($path)) {
                if ($disk->exists($finalPath)) {
                    $disk->delete($finalPath);
                }

                $disk->move($path, $finalPath);
            }

            if (! $disk->exists($finalPath)) {
                continue;
            }

            Photo::create([
                'model_type' => \App\Models\FoundChild::class,
                'model_id' => $this->record->id,
                'path_original' => $finalPath,
                'file_size' => $disk->size($finalPath),
                'mime_type' => mime_content_type($disk->path($finalPath)) ?: null,
                'is_primary' => $first ? 1 : 0,
            ]);

            $first = false;
        }
    }
}