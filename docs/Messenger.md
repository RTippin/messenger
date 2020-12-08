### GET `/api/messenger` | *api.messenger.info*
#### Response:
```json
{
  "siteName": "Messenger",
  "isProvidersCached": false,
  "apiEndpoint": "/api/messenger",
  "webEndpoint": "/messenger",
  "socketEndpoint": "http://localhost:6001",
  "broadcastDriver": "default",
  "pushNotificationDriver": "default",
  "videoDriver": "janus",
  "knockKnock": true,
  "knockTimeout": 5,
  "onlineStatus": true,
  "onlineCacheLifetime": 4,
  "calling": true,
  "threadInvites": true,
  "threadInvitesMax": 3,
  "providerAvatarUpload": true,
  "providerAvatarRemoval": true,
  "messageDocumentUpload": true,
  "messageDocumentDownload": true,
  "messageImageUpload": true,
  "threadAvatarUpload": true,
  "searchPageCount": 25,
  "threadsIndexCount": 100,
  "threadsPageCount": 25,
  "participantsIndexCount": 500,
  "participantsPageCount": 50,
  "messagesIndexCount": 50,
  "messagesPageCount": 50,
  "callsIndexCount": 25,
  "callsPageCount": 25,
  "providers": {
    "user": {
      "default_avatar": "users.png",
      "searchable": true,
      "friendable": true,
      "mobile_devices": false,
      "provider_interactions": {
        "can_message": [
          "user"
        ],
        "can_search": [
          "user"
        ],
        "can_friend": [
          "user"
        ]
      }
    }
  }
}
```
---
### GET `/api/messenger/settings` | *api.messenger.settings*
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
      "updated_at": "2020-12-08T02:41:05.000000Z"
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
  "owner_type": "App\\Models\\User",
  "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "message_popups": true,
  "message_sound": true,
  "call_ringtone_sound": true,
  "notify_sound": true,
  "dark_mode": true,
  "online_status": 1,
  "created_at": "2020-12-08T01:39:35.000000Z",
  "updated_at": "2020-12-08T01:39:35.000000Z"
}
```
---
### PUT `/api/messenger/settings` | *api.messenger.settings.update*
#### Payload:
```json
{
  "message_popups": true,
  "message_sound": true,
  "call_ringtone_sound": true,
  "notify_sound": true,
  "dark_mode": true,
  "online_status": 2
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
      "updated_at": "2020-12-08T02:45:39.000000Z"
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
  "owner_type": "App\\Models\\User",
  "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "message_popups": true,
  "message_sound": true,
  "call_ringtone_sound": true,
  "notify_sound": true,
  "dark_mode": true,
  "online_status": 2,
  "created_at": "2020-12-08T01:39:35.000000Z",
  "updated_at": "2020-12-08T02:45:53.000000Z"
}
```
---
### POST `/api/messenger/avatar` | *api.messenger.avatar.update*
#### Payload:
```json
{
    "image" : "(binary)"
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
      "updated_at": "2020-12-08T02:41:05.000000Z"
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
  "owner_type": "App\\Models\\User",
  "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "message_popups": true,
  "message_sound": true,
  "call_ringtone_sound": true,
  "notify_sound": true,
  "dark_mode": true,
  "online_status": 1,
  "created_at": "2020-12-08T01:39:35.000000Z",
  "updated_at": "2020-12-08T01:39:35.000000Z"
}
```
---
### DELETE `/api/messenger/avatar` | *api.messenger.avatar.destroy*
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
      "avatar": null,
      "created_at": "2020-12-07T07:57:14.000000Z",
      "updated_at": "2020-12-08T02:38:29.000000Z"
    },
    "api_avatar": {
      "sm": "/api/messenger/images/user/922f8476-bdda-4ebd-b283-be23602c658d/sm/default.png",
      "md": "/api/messenger/images/user/922f8476-bdda-4ebd-b283-be23602c658d/md/default.png",
      "lg": "/api/messenger/images/user/922f8476-bdda-4ebd-b283-be23602c658d/lg/default.png"
    },
    "avatar": {
      "sm": "/images/user/922f8476-bdda-4ebd-b283-be23602c658d/sm/default.png",
      "md": "/images/user/922f8476-bdda-4ebd-b283-be23602c658d/md/default.png",
      "lg": "/images/user/922f8476-bdda-4ebd-b283-be23602c658d/lg/default.png"
    }
  },
  "owner_type": "App\\Models\\User",
  "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
  "message_popups": true,
  "message_sound": true,
  "call_ringtone_sound": true,
  "notify_sound": true,
  "dark_mode": true,
  "online_status": 1,
  "created_at": "2020-12-08T01:39:35.000000Z",
  "updated_at": "2020-12-08T01:39:35.000000Z"
}
```
---
### POST `/api/messenger/heartbeat` | *api.messenger.heartbeat*
#### Payload:
```json
{
    "away" : false
}
```
#### Response:
```json
{
  "provider": {
    "name": "John Doe",
    "route": null,
    "provider_id": "922f8476-bdda-4ebd-b283-be23602c658d",
    "provider_alias": "user",
    "base": {
      "id": "922f8476-bdda-4ebd-b283-be23602c658d",
      "name": "John Doe",
      "email": "john@example.net",
      "avatar": "img_5fcee7c1e64404.55920965.jpg",
      "email_verified_at": "2020-12-07T07:57:14.000000Z",
      "created_at": "2020-12-07T07:57:14.000000Z",
      "updated_at": "2020-12-08T02:43:39.000000Z"
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
  "active_calls_count": 0,
  "online_status": 1,
  "online_status_verbose": "ONLINE",
  "unread_threads_count": 0,
  "pending_friends_count": 0,
  "settings": {
    "owner_type": "App\\Models\\User",
    "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
    "message_popups": true,
    "message_sound": true,
    "call_ringtone_sound": true,
    "notify_sound": true,
    "dark_mode": true,
    "online_status": 1,
    "created_at": "2020-12-08T01:39:35.000000Z",
    "updated_at": "2020-12-08T01:39:35.000000Z"
  }
}
```
---
### GET `/api/messenger/unread-threads-count` | *api.messenger.unread.threads.count*
#### Response:
```json
{
  "unread_threads_count": 0
}
```
---
### GET `/api/messenger/search/{query}` | *api.messenger.search*
#### Response:
```json
{
  "data": [
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
        "updated_at": "2020-12-08T02:53:36.000000Z"
      },
      "options": {
        "can_message_first": true,
        "friendable": true,
        "can_friend": true,
        "searchable": true,
        "can_search": true,
        "online_status": 2,
        "online_status_verbose": "AWAY",
        "friend_status": 0,
        "friend_status_verbose": "NOT_FRIEND",
        "last_active": null
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
  ],
  "links": {
    "first": "http://localhost:8000/api/messenger/search/jane?page=1",
    "last": "http://localhost:8000/api/messenger/search/jane?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://localhost:8000/api/messenger/search/jane?page=1",
        "label": 1,
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "path": "http://localhost:8000/api/messenger/search/jane",
    "per_page": 25,
    "to": 1,
    "total": 1,
    "search": "jane",
    "search_items": [
      "jane"
    ]
  }
}
```
---
### GET `/api/messenger/active-calls` | *api.messenger.active.calls*
#### Response:
```json
[
  {
    "id": "923142e6-88bd-48c6-a253-94f1fd587686",
    "active": true,
    "type": 1,
    "type_verbose": "VIDEO",
    "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
    "created_at": "2020-12-08T04:45:34.000000Z",
    "updated_at": "2020-12-08T04:45:35.000000Z",
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
        "updated_at": "2020-12-08T04:45:53.000000Z"
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
    "meta": {
      "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
      "thread_type": 1,
      "thread_type_verbose": "PRIVATE",
      "thread_name": "Jane Doe",
      "api_thread_avatar": {
        "sm": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/sm/default.png",
        "md": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/md/default.png",
        "lg": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/lg/default.png"
      },
      "thread_avatar": {
        "sm": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/sm/default.png",
        "md": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/md/default.png",
        "lg": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/lg/default.png"
      }
    },
    "options": {
      "admin": false,
      "setup_complete": true,
      "in_call": false,
      "left_call": false,
      "joined": false,
      "kicked": false
    }
  }
]
```