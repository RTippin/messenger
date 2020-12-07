<?php

namespace RTippin\Messenger\Http\Collections;

use RTippin\Messenger\Http\Collections\Base\MessengerCollection;
use Exception;
use Illuminate\Http\Request;
use RTippin\Messenger\Http\Resources\SentFriendResource;
use Throwable;

class SentFriendCollection extends MessengerCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public function toArray($request): array
    {
        return $this->safeTransformer();
    }

    /**
     * @inheritDoc
     */
    protected function makeResource($sent): ?array
    {
        try{
            return (new SentFriendResource($sent))->resolve();
        }catch (Exception $e){
            report($e);
        }catch(Throwable $t){
            report($t);
        }

        return null;
    }
}
