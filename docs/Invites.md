### GET `/api/messenger/threads/{thread}/invites` | *api.messenger.threads.invites.index*
#### Response:
```json
{
  "data": [
    {
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
          "updated_at": "2020-12-08T05:53:08.000000Z"
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
      "route": "http://localhost:8000/messenger/join/ZAJOR26J",
      "id": "92315b42-d57e-461a-91fb-e5eb9f1f2ca9",
      "thread_id": "92313cf7-b356-4d74-944c-c799fcfc3b1e",
      "owner_type": "App\\Models\\User",
      "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
      "code": "ZAJOR26J",
      "max_use": 0,
      "uses": 0,
      "expires_at": null,
      "created_at": "2020-12-08T05:53:41.000000Z",
      "updated_at": "2020-12-08T05:53:41.000000Z",
      "deleted_at": null
    },
    {
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
          "updated_at": "2020-12-08T05:53:08.000000Z"
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
      "route": "http://localhost:8000/messenger/join/7EIFRXXP",
      "id": "92315aa0-c44b-4da8-8b7f-5d5529412874",
      "thread_id": "92313cf7-b356-4d74-944c-c799fcfc3b1e",
      "owner_type": "App\\Models\\User",
      "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
      "code": "7EIFRXXP",
      "max_use": 25,
      "uses": 0,
      "expires_at": "2020-12-22T05:51:55.000000Z",
      "created_at": "2020-12-08T05:51:55.000000Z",
      "updated_at": "2020-12-08T05:51:55.000000Z",
      "deleted_at": null
    }
  ],
  "meta": {
    "total": 2,
    "max_allowed": 3
  }
}
```
---
### POST `/api/messenger/threads/{thread}/invites` | *api.messenger.threads.invites.store*
#### Payload:
```json
{
  "expires": 7, 
  "uses": 25
}
```
#### Response:
```json
{
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
      "updated_at": "2020-12-08T05:51:08.000000Z"
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
  "route": "http://localhost:8000/messenger/join/7EIFRXXP",
  "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "owner_type": "App\\Models\\User",
  "code": "7EIFRXXP",
  "max_use": 25,
  "uses": 0,
  "expires_at": "2020-12-22T05:51:55.000000Z",
  "thread_id": "92313cf7-b356-4d74-944c-c799fcfc3b1e",
  "id": "92315aa0-c44b-4da8-8b7f-5d5529412874",
  "updated_at": "2020-12-08T05:51:55.000000Z",
  "created_at": "2020-12-08T05:51:55.000000Z"
}
```
---
### DELETE `/api/messenger/threads/{thread}/invites/{invite}` | *api.messenger.threads.invites.destroy*
#### Response:
```json
{
  "message": "success"
}
```
---
### GET `/api/messenger/join/{invite:code}` | *api.messenger.invites.join*
#### Response:
```json
{
  "options": {
    "messenger_auth": true,
    "in_thread": false,
    "thread_name": "Test Party!",
    "is_valid": true
  },
  "route": "http://localhost:8000/messenger/join/ZAJOR26J",
  "id": "92315b42-d57e-461a-91fb-e5eb9f1f2ca9",
  "thread_id": "92313cf7-b356-4d74-944c-c799fcfc3b1e",
  "owner_type": "App\\Models\\User",
  "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "code": "ZAJOR26J",
  "max_use": 0,
  "uses": 0,
  "expires_at": null,
  "created_at": "2020-12-08T05:53:41.000000Z",
  "updated_at": "2020-12-08T05:53:41.000000Z",
  "deleted_at": null
}
```
---
### POST `/api/messenger/join/{invite:code}` | *api.messenger.invites.join.store*
#### Response:
```json
{
  "add_participants": false,
  "manage_invites": false,
  "admin": false,
  "deleted_at": null,
  "pending": false,
  "start_calls": false,
  "send_knocks": false,
  "send_messages": true,
  "owner_id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
  "owner_type": "App\\Models\\User",
  "thread_id": "92313cf7-b356-4d74-944c-c799fcfc3b1e",
  "id": "9231603f-6513-41dd-bced-aae9da5cd932",
  "updated_at": "2020-12-08T06:07:38.000000Z",
  "created_at": "2020-12-08T06:07:38.000000Z",
  "owner": {
    "id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
    "name": "Jairo O'Kon I",
    "avatar": null,
    "created_at": "2020-12-07T07:57:15.000000Z",
    "updated_at": "2020-12-08T06:07:34.000000Z"
  }
}
```