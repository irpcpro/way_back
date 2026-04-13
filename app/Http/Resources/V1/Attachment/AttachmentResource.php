<?php

namespace App\Http\Resources\V1\Attachment;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;
use Vinkla\Hashids\Facades\Hashids;

class AttachmentResource extends JsonResource
{

    public function __construct(Attachment $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array | null
    {
        if($this->resource == null)
            return null;

        $hashids = Hashids::encode($this->id_attachment, $this->id_message_hook);

        return [
            'id_attachment' => $this->id_attachment,
            'url' => route('attach.view', [
                'id' => $this->id_attachment,
                'auth' => $hashids,
                'rnd' => rand(1,100)
            ]),
            'name' => $this->name,
            'type' => $this->type,
            'extension' => $this->extension,
            'size_b' => (int)$this->size_b,
            'created_at' => $this->created_at,
        ];
    }
}
