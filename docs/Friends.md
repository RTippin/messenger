### GET `/api/messenger/friends` | *api.messenger.friends.index*
#### Response:
```json
[
  {
    "party": {
      "name": "Jane Doe",
      "route": null,
      "provider_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
      "provider_alias": "user",
      "base": {
        "id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
        "name": "Jane Doe",
        "avatar": null,
        "created_at": "2020-12-07T07:57:14.000000Z",
        "updated_at": "2020-12-08T03:51:36.000000Z"
      },
      "options": {
        "can_message_first": true,
        "friendable": true,
        "can_friend": true,
        "searchable": true,
        "can_search": true,
        "online_status": 1,
        "online_status_verbose": "ONLINE",
        "friend_status": 1,
        "friend_status_verbose": "FRIEND",
        "last_active": "2020-12-08T03:51:36.000000Z",
        "friend_id": "92312fc9-6b24-42c8-9dcc-34a64bc6b55b"
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
    "type_verbose": "FRIEND",
    "id": "92312fc9-6b24-42c8-9dcc-34a64bc6b55b",
    "owner_type": "App\\Models\\User",
    "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
    "party_type": "App\\Models\\User",
    "party_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
    "created_at": "2020-12-08T03:52:07.000000Z",
    "updated_at": "2020-12-08T03:52:07.000000Z"
  }
]
```
---
### GET `/api/messenger/friends/{friend}` | *api.messenger.friends.show*
#### Response:
```json
{
  "party": {
    "name": "Jane Doe",
    "route": null,
    "provider_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
    "provider_alias": "user",
    "base": {
      "id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
      "name": "Jane Doe",
      "avatar": null,
      "created_at": "2020-12-07T07:57:14.000000Z",
      "updated_at": "2020-12-08T03:51:36.000000Z"
    },
    "options": {
      "can_message_first": true,
      "friendable": true,
      "can_friend": true,
      "searchable": true,
      "can_search": true,
      "online_status": 1,
      "online_status_verbose": "ONLINE",
      "friend_status": 1,
      "friend_status_verbose": "FRIEND",
      "last_active": "2020-12-08T03:51:36.000000Z",
      "friend_id": "92312fc9-6b24-42c8-9dcc-34a64bc6b55b"
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
  "type_verbose": "FRIEND",
  "id": "92312fc9-6b24-42c8-9dcc-34a64bc6b55b",
  "owner_type": "App\\Models\\User",
  "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "party_type": "App\\Models\\User",
  "party_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "created_at": "2020-12-08T03:52:07.000000Z",
  "updated_at": "2020-12-08T03:52:07.000000Z"
}
```
---
### DELETE `/api/messenger/friends/{friend}` | *api.messenger.friends.destroy*
#### Response:
```json
{
  "name": "Jane Doe",
  "route": null,
  "provider_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "provider_alias": "user",
  "base": {
    "id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
    "name": "Jane Doe",
    "avatar": null,
    "created_at": "2020-12-07T07:57:14.000000Z",
    "updated_at": "2020-12-08T03:53:36.000000Z"
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
}
```
---
### POST `/api/messenger/friends/sent` | *api.messenger.friends.sent.store*
#### Payload:
```json
{
  "recipient_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "recipient_alias": "user"
}
```
#### Response:
```json
{
  "recipient": {
    "name": "Jane Doe",
    "route": null,
    "provider_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
    "provider_alias": "user",
    "base": {
      "id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
      "name": "Jane Doe",
      "avatar": null,
      "created_at": "2020-12-07T07:57:14.000000Z",
      "updated_at": "2020-12-08T02:59:36.000000Z"
    },
    "options": {
      "can_message_first": true,
      "friendable": true,
      "can_friend": true,
      "searchable": true,
      "can_search": true,
      "online_status": 2,
      "online_status_verbose": "AWAY",
      "friend_status": 2,
      "friend_status_verbose": "SENT_FRIEND_REQUEST",
      "last_active": null,
      "sent_friend_id": null
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
  "type_verbose": "SENT_FRIEND_REQUEST",
  "sender_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "sender_type": "App\\Models\\User",
  "recipient_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "recipient_type": "App\\Models\\User",
  "id": "92311d70-46a3-48d2-be8d-a1f5b6231ec0",
  "updated_at": "2020-12-08T03:00:49.000000Z",
  "created_at": "2020-12-08T03:00:49.000000Z"
}
```
---
### GET `/api/messenger/friends/sent` | *api.messenger.friends.sent.index*
#### Response:
```json
[
  {
    "recipient": {
      "name": "Jane Doe",
      "route": null,
      "provider_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
      "provider_alias": "user",
      "base": {
        "id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
        "name": "Jane Doe",
        "avatar": null,
        "created_at": "2020-12-07T07:57:14.000000Z",
        "updated_at": "2020-12-08T03:09:36.000000Z"
      },
      "options": {
        "can_message_first": true,
        "friendable": true,
        "can_friend": true,
        "searchable": true,
        "can_search": true,
        "online_status": 2,
        "online_status_verbose": "AWAY",
        "friend_status": 2,
        "friend_status_verbose": "SENT_FRIEND_REQUEST",
        "last_active": null,
        "sent_friend_id": "92311d70-46a3-48d2-be8d-a1f5b6231ec0"
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
    "type_verbose": "SENT_FRIEND_REQUEST",
    "id": "92311d70-46a3-48d2-be8d-a1f5b6231ec0",
    "sender_type": "App\\Models\\User",
    "sender_id": "922f8476-bdda-4ebd-b283-be23602c658d",
    "recipient_type": "App\\Models\\User",
    "recipient_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
    "created_at": "2020-12-08T03:00:49.000000Z",
    "updated_at": "2020-12-08T03:00:49.000000Z"
  }
]
```
---
### GET `/api/messenger/friends/sent/{sent}` | *api.messenger.friends.sent.show*
#### Response:
```json
{
  "recipient": {
    "name": "Jane Doe",
    "route": null,
    "provider_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
    "provider_alias": "user",
    "base": {
      "id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
      "name": "Jane Doe",
      "avatar": null,
      "created_at": "2020-12-07T07:57:14.000000Z",
      "updated_at": "2020-12-08T03:48:05.000000Z"
    },
    "options": {
      "can_message_first": true,
      "friendable": true,
      "can_friend": true,
      "searchable": true,
      "can_search": true,
      "online_status": 1,
      "online_status_verbose": "ONLINE",
      "friend_status": 2,
      "friend_status_verbose": "SENT_FRIEND_REQUEST",
      "last_active": null,
      "sent_friend_id": "92312e58-42e4-44a6-92a6-6aff5b8f9fcb"
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
  "type_verbose": "SENT_FRIEND_REQUEST",
  "id": "92312e58-42e4-44a6-92a6-6aff5b8f9fcb",
  "sender_type": "App\\Models\\User",
  "sender_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "recipient_type": "App\\Models\\User",
  "recipient_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "created_at": "2020-12-08T03:48:05.000000Z",
  "updated_at": "2020-12-08T03:48:05.000000Z"
}
```
---
### DELETE `/api/messenger/friends/sent/{sent}` | *api.messenger.friends.sent.destroy*
#### Response:
```json
{
  "name": "Jane Doe",
  "route": null,
  "provider_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "provider_alias": "user",
  "base": {
    "id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
    "name": "Jane Doe",
    "avatar": null,
    "created_at": "2020-12-07T07:57:14.000000Z",
    "updated_at": "2020-12-08T03:40:00.000000Z"
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
}
```
---
### GET `/api/messenger/friends/pending` | *api.messenger.friends.pending.index*
#### Response:
```json
[
  {
    "sender": {
      "name": "John Doe",
      "route": null,
      "provider_id": "922f8476-bdda-4ebd-b283-be23602c658d",
      "provider_alias": "user",
      "base": {
        "id": "922f8476-bdda-4ebd-b283-be23602c658d",
        "name": "John Doe",
        "avatar": "img_5fcee7c1e64404.55920965.jpg",
        "created_at": "2020-12-07T07:57:14.000000Z",
        "updated_at": "2020-12-08T03:09:39.000000Z"
      },
      "options": {
        "can_message_first": true,
        "friendable": true,
        "can_friend": true,
        "searchable": true,
        "can_search": true,
        "online_status": 1,
        "online_status_verbose": "ONLINE",
        "friend_status": 3,
        "friend_status_verbose": "PENDING_FRIEND_REQUEST",
        "last_active": null,
        "pending_friend_id": "92311d70-46a3-48d2-be8d-a1f5b6231ec0"
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
    "type_verbose": "PENDING_FRIEND_REQUEST",
    "id": "92311d70-46a3-48d2-be8d-a1f5b6231ec0",
    "sender_type": "App\\Models\\User",
    "sender_id": "922f8476-bdda-4ebd-b283-be23602c658d",
    "recipient_type": "App\\Models\\User",
    "recipient_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
    "created_at": "2020-12-08T03:00:49.000000Z",
    "updated_at": "2020-12-08T03:00:49.000000Z"
  }
]
```
---
### GET `/api/messenger/friends/pending/{pending}` | *api.messenger.friends.pending.show*
#### Response:
```json
{
  "sender": {
    "name": "John Doe",
    "route": null,
    "provider_id": "922f8476-bdda-4ebd-b283-be23602c658d",
    "provider_alias": "user",
    "base": {
      "id": "922f8476-bdda-4ebd-b283-be23602c658d",
      "name": "John Doe",
      "avatar": "img_5fcee7c1e64404.55920965.jpg",
      "created_at": "2020-12-07T07:57:14.000000Z",
      "updated_at": "2020-12-08T03:49:40.000000Z"
    },
    "options": {
      "can_message_first": true,
      "friendable": true,
      "can_friend": true,
      "searchable": true,
      "can_search": true,
      "online_status": 1,
      "online_status_verbose": "ONLINE",
      "friend_status": 3,
      "friend_status_verbose": "PENDING_FRIEND_REQUEST",
      "last_active": null,
      "pending_friend_id": "92312e58-42e4-44a6-92a6-6aff5b8f9fcb"
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
  "type_verbose": "PENDING_FRIEND_REQUEST",
  "id": "92312e58-42e4-44a6-92a6-6aff5b8f9fcb",
  "sender_type": "App\\Models\\User",
  "sender_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "recipient_type": "App\\Models\\User",
  "recipient_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "created_at": "2020-12-08T03:48:05.000000Z",
  "updated_at": "2020-12-08T03:48:05.000000Z"
}
```
---
### DELETE `/api/messenger/friends/pending/{pending}` | *api.messenger.friends.pending.destroy*
#### Response:
```json
{
  "name": "John Doe",
  "route": null,
  "provider_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "provider_alias": "user",
  "base": {
    "id": "922f8476-bdda-4ebd-b283-be23602c658d",
    "name": "John Doe",
    "avatar": "img_5fcee7c1e64404.55920965.jpg",
    "created_at": "2020-12-07T07:57:14.000000Z",
    "updated_at": "2020-12-08T03:37:08.000000Z"
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
}
```
---
### PUT `/api/messenger/friends/pending/{pending}` | *api.messenger.friends.pending.update*
#### Payload:
```json
{}
```
#### Response:
```json
{
  "party": {
    "name": "John Doe",
    "route": null,
    "provider_id": "922f8476-bdda-4ebd-b283-be23602c658d",
    "provider_alias": "user",
    "base": {
      "id": "922f8476-bdda-4ebd-b283-be23602c658d",
      "name": "John Doe",
      "avatar": "img_5fcee7c1e64404.55920965.jpg",
      "created_at": "2020-12-07T07:57:14.000000Z",
      "updated_at": "2020-12-08T03:41:40.000000Z"
    },
    "options": {
      "can_message_first": true,
      "friendable": true,
      "can_friend": true,
      "searchable": true,
      "can_search": true,
      "online_status": 1,
      "online_status_verbose": "ONLINE",
      "friend_status": 1,
      "friend_status_verbose": "FRIEND",
      "last_active": "2020-12-08T03:41:40.000000Z",
      "friend_id": "92312cb2-3390-44a6-a8be-0d052b4395a5"
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
  "type_verbose": "FRIEND",
  "owner_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "owner_type": "App\\Models\\User",
  "party_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "party_type": "App\\Models\\User",
  "id": "92312cb2-3390-44a6-a8be-0d052b4395a5",
  "updated_at": "2020-12-08T03:43:29.000000Z",
  "created_at": "2020-12-08T03:43:29.000000Z"
}
```