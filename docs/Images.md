### GET `/api/messenger/threads/{thread}/images` | *api.messenger.threads.images.index*
#### Response:
```json
{
  "data": [
    {
      "id": "923151bb-d14c-4f6a-986e-6aa017fbc9fa",
      "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
      "owner_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
      "owner_type": "App\\Models\\User",
      "owner": {
        "name": "Jane Doe",
        "route": null,
        "provider_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
        "provider_alias": "user",
        "base": {
          "id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
          "name": "Jane Doe",
          "avatar": null,
          "created_at": "2020-12-07T07:57:14.000000Z",
          "updated_at": "2020-12-08T05:39:53.000000Z"
        },
        "api_avatar": {
          "sm": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/sm/default.png",
          "md": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/md/default.png",
          "lg": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/lg/default.png"
        },
        "avatar": {
          "sm": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/sm/default.png",
          "md": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/md/default.png",
          "lg": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/lg/default.png"
        }
      },
      "type": 1,
      "type_verbose": "IMAGE_MESSAGE",
      "system_message": false,
      "body": "img_5fcf0ea71dcb17.99881745.jpg",
      "created_at": "2020-12-08T05:27:03.000000Z",
      "updated_at": "2020-12-08T05:27:03.000000Z",
      "meta": {
        "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
        "thread_type": 1,
        "thread_type_verbose": "PRIVATE"
      },
      "api_image": {
        "sm": "/api/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/923151bb-d14c-4f6a-986e-6aa017fbc9fa/sm/img_5fcf0ea71dcb17.99881745.jpg",
        "md": "/api/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/923151bb-d14c-4f6a-986e-6aa017fbc9fa/md/img_5fcf0ea71dcb17.99881745.jpg",
        "lg": "/api/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/923151bb-d14c-4f6a-986e-6aa017fbc9fa/lg/img_5fcf0ea71dcb17.99881745.jpg"
      },
      "image": {
        "sm": "/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/923151bb-d14c-4f6a-986e-6aa017fbc9fa/sm/img_5fcf0ea71dcb17.99881745.jpg",
        "md": "/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/923151bb-d14c-4f6a-986e-6aa017fbc9fa/md/img_5fcf0ea71dcb17.99881745.jpg",
        "lg": "/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/923151bb-d14c-4f6a-986e-6aa017fbc9fa/lg/img_5fcf0ea71dcb17.99881745.jpg"
      }
    }
  ],
  "meta": {
    "index": true,
    "page_id": null,
    "next_page_id": null,
    "next_page_route": null,
    "final_page": true,
    "per_page": 50,
    "results": 1,
    "total": 1
  }
}
```
---
### POST `/api/messenger/threads/{thread}/images` | *api.messenger.threads.images.store*
#### Payload:
```json
{
  "image" : "(binary)",
  "temporary_id" : "33234580-3918-11eb-985e-e58d0602db52"
}
```
#### Response:
```json
{
  "id": "9231575c-703e-4038-a91b-4034a8119f94",
  "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
  "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "owner_type": "App\\Models\\User",
  "owner": {
    "name": "John Doe",
    "route": null,
    "provider_id": "922f8476-bdda-4ebd-b283-be23602c658d",
    "provider_alias": "user",
    "base": {
      "id": "922f8476-bdda-4ebd-b283-be23602c658d",
      "name": "John Doe",
      "avatar": "img_5fcee7c1e64404.55920965.jpg",
      "created_at": "2020-12-07T07:57:14.000000Z",
      "updated_at": "2020-12-08T05:41:08.000000Z"
    },
    "api_avatar": {
      "sm": "/api/messenger/images/user/922f8476-bdda-4ebd-b283-be23602c658d/sm/img_5fcee7c1e64404.55920965.jpg",
      "md": "/api/messenger/images/user/922f8476-bdda-4ebd-b283-be23602c658d/md/img_5fcee7c1e64404.55920965.jpg",
      "lg": "/api/messenger/images/user/922f8476-bdda-4ebd-b283-be23602c658d/lg/img_5fcee7c1e64404.55920965.jpg"
    },
    "avatar": {
      "sm": "/images/user/922f8476-bdda-4ebd-b283-be23602c658d/sm/img_5fcee7c1e64404.55920965.jpg",
      "md": "/images/user/922f8476-bdda-4ebd-b283-be23602c658d/md/img_5fcee7c1e64404.55920965.jpg",
      "lg": "/images/user/922f8476-bdda-4ebd-b283-be23602c658d/lg/img_5fcee7c1e64404.55920965.jpg"
    }
  },
  "type": 1,
  "type_verbose": "IMAGE_MESSAGE",
  "system_message": false,
  "body": "img_5fcf12573c2f76.23243115.jpg",
  "created_at": "2020-12-08T05:42:47.000000Z",
  "updated_at": "2020-12-08T05:42:47.000000Z",
  "meta": {
    "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
    "thread_type": 1,
    "thread_type_verbose": "PRIVATE"
  },
  "temporary_id": "33234580-3918-11eb-985e-e58d0602db52",
  "api_image": {
    "sm": "/api/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/9231575c-703e-4038-a91b-4034a8119f94/sm/img_5fcf12573c2f76.23243115.jpg",
    "md": "/api/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/9231575c-703e-4038-a91b-4034a8119f94/md/img_5fcf12573c2f76.23243115.jpg",
    "lg": "/api/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/9231575c-703e-4038-a91b-4034a8119f94/lg/img_5fcf12573c2f76.23243115.jpg"
  },
  "image": {
    "sm": "/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/9231575c-703e-4038-a91b-4034a8119f94/sm/img_5fcf12573c2f76.23243115.jpg",
    "md": "/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/9231575c-703e-4038-a91b-4034a8119f94/md/img_5fcf12573c2f76.23243115.jpg",
    "lg": "/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/gallery/9231575c-703e-4038-a91b-4034a8119f94/lg/img_5fcf12573c2f76.23243115.jpg"
  }
}
```