<?php

namespace App\Modules\Cms\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Cms\Actions\UploadEditorImageAction;
use App\Modules\Cms\Models\WebsitePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * POST gambar dari editor rich text → { url }.
 */
class EditorImageController extends Controller
{
    public function __invoke(Request $request, UploadEditorImageAction $upload): JsonResponse
    {
        $this->authorize('manage', WebsitePage::class);
        $request->validate(['image' => ['required', 'file', 'max:5120']], [], ['image' => 'gambar']);

        /** @var UploadedFile $file */
        $file = $request->file('image');
        /** @var User $user */
        $user = $request->user();

        return response()->json($upload->handle($file, $user), 201);
    }
}
