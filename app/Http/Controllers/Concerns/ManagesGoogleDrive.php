<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\GoogleAccountDisconnectedException;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait ManagesGoogleDrive
{
    protected function uploadFileToGoogleDrive(Request $request, string $fileInputName, string $kraFolderName, ?string $subFolderName = null): string
    {
        $this->ensureGoogleDriveIsConnected(auth()->user(), 'upload');
        $file = $request->file($fileInputName);
        $service = $this->getGoogleDriveService(auth()->user());
        $mainFolderId = $this->findOrCreateFolder($service, 'Autorank Files');
        $kraFolderId = $this->findOrCreateFolder($service, $kraFolderName, $mainFolderId);
        $targetFolderId = $subFolderName ? $this->findOrCreateFolder($service, $subFolderName, $kraFolderId) : $kraFolderId;
        $fileName = time() . '_' . $file->getClientOriginalName();
        $fileMetadata = new DriveFile(['name' => $fileName, 'parents' => [$targetFolderId]]);
        $content = file_get_contents($file->getRealPath());
        $uploadedFile = $service->files->create($fileMetadata, ['data' => $content, 'mimeType' => $file->getClientMimeType(), 'uploadType' => 'multipart', 'fields' => 'id']);
        return $uploadedFile->id;
    }

    protected function deleteFileFromGoogleDrive(string $fileId, User $fileOwner): void
    {
        $this->ensureGoogleDriveIsConnected($fileOwner, 'delete');
        try {
            $service = $this->getGoogleDriveService($fileOwner);
            $service->files->delete($fileId);
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                Log::info('Attempted to delete a Google Drive file that was already gone.', ['file_id' => $fileId]);
            } else {
                throw $e;
            }
        }
    }

    protected function viewFileById($fileId, Request $request, User $fileOwner)
    {
        if (!$fileId) {
            return response()->json(['message' => 'File ID not found.'], 404);
        }

        try {
            $isOwnerViewing = Auth::id() === $fileOwner->id;
            $this->ensureGoogleDriveIsConnected($fileOwner, 'view', $isOwnerViewing);

            $service = $this->getGoogleDriveService($fileOwner);
            $response = $service->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();
            $fileMeta = $service->files->get($fileId, ['fields' => 'mimeType, name']);

            return response($content, 200)
                ->header('Content-Type', $fileMeta->getMimeType())
                ->header('Content-Disposition', 'inline; filename="' . $fileMeta->getName() . '"');
        } catch (GoogleAccountDisconnectedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Google Drive file viewing failed: ' . $e->getMessage(), ['user_id' => $fileOwner->id]);
            return response()->json(['message' => 'Could not retrieve the file. Please try again later.'], 500);
        }
    }

    private function getGoogleDriveService(User $user): Drive
    {
        if (empty($user->google_refresh_token)) {
            throw new \Exception('Google refresh token is missing.');
        }
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->refreshToken($user->google_refresh_token);
        return new Drive($client);
    }

    private function ensureGoogleDriveIsConnected(User $user, string $action, bool $isOwner = true): void
    {
        if (!empty($user->google_refresh_token)) {
            return;
        }

        $message = '';
        switch ($action) {
            case 'upload':
                $message = 'Upload Failed: Please allow Google Drive access on the settings page before uploading new files.';
                break;
            case 'delete':
                $message = 'Deletion Failed: Please allow Google Drive access on the settings page before deleting existing files.';
                break;
            case 'view':
                if ($isOwner) {
                    $message = 'Cannot View File: Please allow Google Drive access on the settings page to view your files directly on the website.';
                } else {
                    $message = "Cannot Access File: The file could not be retrieved because the owner's Google Drive account access been revoked.";
                }
                break;
            default:
                $message = 'Your Google Drive account access is disconnected. Please reconnect it on the settings page.';
                break;
        }

        throw new GoogleAccountDisconnectedException($message);
    }

    private function findOrCreateFolder(Drive $service, string $folderName, ?string $parentId = null): string
    {
        $query = "mimeType='application/vnd.google-apps.folder' and name='$folderName' and trashed=false";
        if ($parentId) {
            $query .= " and '$parentId' in parents";
        }
        $response = $service->files->listFiles(['q' => $query, 'fields' => 'files(id)']);
        if (count($response->getFiles()) > 0) {
            return $response->getFiles()[0]->getId();
        }
        $folderMetadata = new DriveFile(['name' => $folderName, 'mimeType' => 'application/vnd.google-apps.folder']);
        if ($parentId) {
            $folderMetadata->setParents([$parentId]);
        }
        $folder = $service->files->create($folderMetadata, ['fields' => 'id']);
        return $folder->id;
    }
}
