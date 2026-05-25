<?php

namespace App\Filament\Admin\Resources\FoundChildResource\Pages;

use App\Filament\Admin\Resources\FoundChildResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

use App\Models\Photo;

class CreateFoundChild extends CreateRecord
{
    protected static string $resource = FoundChildResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getState();
        if (!empty($state['photo_upload'])) {
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