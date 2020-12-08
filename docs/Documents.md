### GET `/api/messenger/threads/{thread}/documents` | *api.messenger.threads.documents.index*
#### Response:
```json
{
  "data": [
    {
      "id": "92315377-0a5c-47ed-928e-45670dd6c341",
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
          "updated_at": "2020-12-08T05:39:08.000000Z"
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
      "type": 2,
      "type_verbose": "DOCUMENT_MESSAGE",
      "system_message": false,
      "body": "testPdf_1607405513.pdf",
      "created_at": "2020-12-08T05:31:53.000000Z",
      "updated_at": "2020-12-08T05:31:53.000000Z",
      "meta": {
        "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
        "thread_type": 1,
        "thread_type_verbose": "PRIVATE"
      },
      "api_document": "/api/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/files/92315377-0a5c-47ed-928e-45670dd6c341/testPdf_1607405513.pdf",
      "document": "/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/files/92315377-0a5c-47ed-928e-45670dd6c341/testPdf_1607405513.pdf"
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
### POST `/api/messenger/threads/{thread}/documents` | *api.messenger.threads.documents.store*
#### Payload:
```json
{
  "document" : "(binary)",
  "temporary_id" : "adb9c9b0-3916-11eb-985e-e58d0602db52"
}
```
#### Response:
```json
{
  "id": "92315377-0a5c-47ed-928e-45670dd6c341",
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
      "updated_at": "2020-12-08T05:31:08.000000Z"
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
  "type": 2,
  "type_verbose": "DOCUMENT_MESSAGE",
  "system_message": false,
  "body": "testPdf_1607405513.pdf",
  "created_at": "2020-12-08T05:31:53.000000Z",
  "updated_at": "2020-12-08T05:31:53.000000Z",
  "meta": {
    "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
    "thread_type": 1,
    "thread_type_verbose": "PRIVATE"
  },
  "temporary_id": "adb9c9b0-3916-11eb-985e-e58d0602db52",
  "api_document": "/api/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/files/92315377-0a5c-47ed-928e-45670dd6c341/testPdf_1607405513.pdf",
  "document": "/messenger/threads/923135d4-bcaa-4aa7-83a9-cc9765866b19/files/92315377-0a5c-47ed-928e-45670dd6c341/testPdf_1607405513.pdf"
}
```