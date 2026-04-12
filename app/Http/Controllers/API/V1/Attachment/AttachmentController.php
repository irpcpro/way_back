<?php

namespace App\Http\Controllers\API\V1\Attachment;

use App\Enums\AttachmentTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\AttachmentRequest;
use App\Http\Resources\V1\Attachment\AttachmentResource;
use App\Models\Attachment;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use function APIResponse;
use function auth;
use function public_path;
use const ATTACHMENT_DIR_ACCESS;
use const ATTACHMENT_DIR_NAME;
use const ATTACHMENT_IMAGE_DIR_ACCESS;
use const ATTACHMENT_IMAGE_DIR_NAME;
use const ATTACHMENT_IMAGE_FINAL_EXT;
use const ATTACHMENT_IMAGE_FINAL_QUALITY;

class AttachmentController extends Controller
{

    public function upload(AttachmentRequest $request, int $id_message_hook)
    {
        // get attachment field
        $attachment = $request->validated('attachment');
//        $type = (int)$request->validated('type');
        $current_user = auth()->user();

        // check if id message hook is for current user or not
        if(!$current_user->message_hook_member()->where('id_message_hook', $id_message_hook)->exists())
            APIResponse('something went wrong.', 402, true)->setData([])->send();

        // create attachment name & path
        $attachment_name = time() . chr(rand(97,122)) . rand(11111,99999);
        $attachment_path = ATTACHMENT_DIR_NAME . '/';
        $upload_attachment_path = public_path($attachment_path);

        // create attachment path if not exists
        if(!File::exists($upload_attachment_path))
            File::makeDirectory($upload_attachment_path, ATTACHMENT_DIR_ACCESS, true);


        // move attachment to host and resize if it's image and change extension image
//        if($type == AttachmentTypeEnum::IMAGE->value){
        $final_extension = ATTACHMENT_IMAGE_FINAL_EXT;
        $attachment_final_type_path = ATTACHMENT_IMAGE_DIR_NAME . '/';
        $upload_final_type_path = public_path($attachment_final_type_path);

        // create attachment path if not exists
        if(!File::exists($upload_final_type_path))
            File::makeDirectory($upload_final_type_path, ATTACHMENT_IMAGE_DIR_ACCESS, true);

        $path = storage_path(ATTACHMENT_PRIVATE_PATH);
        if (!File::exists($path)) File::makeDirectory($path, 0775, true);

        $final_upload_path = $path . '/' . $attachment_name . '.' . $final_extension;

        Image::make($attachment)
            ->save($final_upload_path)
            ->encode($final_extension, ATTACHMENT_IMAGE_FINAL_QUALITY);
//        }else{

//        }

        // insert attachment details
        $final_attachment = Attachment::create([
            'id_user' => $current_user->id_user,
            'type' => AttachmentTypeEnum::IMAGE->value,
            'name' => $attachment_name,
            'extension' => $final_extension,
            'path' => $attachment_final_type_path,
            'size_b' => File::size($final_upload_path),
            'id_message_hook' => $id_message_hook,
        ]);

        $data = new AttachmentResource($final_attachment);
        $data = $data->toArray($request);

        // send response
        $response = APIResponse('avatar uploaded.', 200, true);
        $response = $response->setData($data);
        $response->send();
    }

    public function view(int $id){
        $current_user = auth()->user();

        $attachment = Attachment::findOrFail($id);

        if(!$current_user->message_hook_member()->where('id_message_hook', $attachment->id_message_hook)->exists())
            abort(404);

        $path = storage_path(ATTACHMENT_PRIVATE_PATH . '/' . $attachment->name . '.' . $attachment->extension);

        if (!File::exists($path))
            abort(404);

        return response()->file($path);
    }

}
