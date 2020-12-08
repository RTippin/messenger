### GET `/api/messenger/threads/{thread}/participants` | *api.messenger.threads.participants.index*
#### Response:
```json
{
    "data": [
        {
            "id": "92313cf7-babd-4105-8068-5d895d734927",
            "admin": true,
            "pending": false,
            "send_knocks": true,
            "send_messages": true,
            "add_participants": true,
            "manage_invites": 1,
            "start_calls": true,
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
                    "updated_at": "2020-12-08T04:51:08.000000Z"
                },
                "options": {
                    "can_message_first": false,
                    "friendable": false,
                    "can_friend": false,
                    "searchable": true,
                    "can_search": true,
                    "online_status": 1,
                    "online_status_verbose": "ONLINE",
                    "friend_status": 0,
                    "friend_status_verbose": "NOT_FRIEND",
                    "last_active": null
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
            "created_at": "2020-12-08T04:28:59.000000Z",
            "updated_at": "2020-12-08T04:51:36.000000Z",
            "last_read": {
                "time": "2020-12-08T04:51:36.000000Z",
                "message_id": "9231450e-0f08-4ac4-950a-d237343f430a"
            }
        },
        {
            "id": "92313cf7-bde5-4dcf-b1c8-b03582985ff0",
            "admin": false,
            "pending": false,
            "send_knocks": false,
            "send_messages": true,
            "add_participants": false,
            "manage_invites": 0,
            "start_calls": false,
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
                    "updated_at": "2020-12-08T04:51:34.000000Z"
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
                    "last_active": "2020-12-08T04:51:34.000000Z",
                    "friend_id": "923136b9-01b1-45d3-a844-c351fa440141"
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
            "created_at": "2020-12-08T04:28:59.000000Z",
            "updated_at": "2020-12-08T04:51:34.000000Z",
            "last_read": {
                "time": "2020-12-08T04:29:05.000000Z",
                "message_id": "92313cfa-f190-4b95-8980-199c841623eb"
            }
        }
    ],
    "meta": {
        "index": true,
        "page_id": null,
        "next_page_id": null,
        "next_page_route": null,
        "final_page": true,
        "per_page": 500,
        "results": 2,
        "total": 2
    }
}
```
---
### GET `/api/messenger/threads/{thread}/participants/{participant}` | *api.messenger.threads.participants.show*
#### Response:
```json
{
  "id": "92313cf7-bde5-4dcf-b1c8-b03582985ff0",
  "admin": false,
  "pending": false,
  "send_knocks": false,
  "send_messages": true,
  "add_participants": false,
  "manage_invites": 0,
  "start_calls": false,
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
      "updated_at": "2020-12-08T05:01:53.000000Z"
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
      "last_active": "2020-12-08T05:01:53.000000Z",
      "friend_id": "923136b9-01b1-45d3-a844-c351fa440141"
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
  "created_at": "2020-12-08T04:28:59.000000Z",
  "updated_at": "2020-12-08T04:59:56.000000Z",
  "last_read": {
    "time": "2020-12-08T04:59:56.000000Z",
    "message_id": "9231450e-0f08-4ac4-950a-d237343f430a"
  }
}
```
---
### POST `/api/messenger/threads/{thread}/participants` | *api.messenger.threads.participants.store*
#### Payload:
```json
{
    "providers": [
        {
            "alias": "user",
            "id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d"
        }
    ]
}
```
#### Response:
```json
[
  {
    "owner_id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
    "owner_type": "App\\Models\\User",
    "thread_id": "92313cf7-b356-4d74-944c-c799fcfc3b1e",
    "id": "923149ab-92bf-405d-826d-95cf222cdb03",
    "updated_at": "2020-12-08T05:04:30.000000Z",
    "created_at": "2020-12-08T05:04:30.000000Z"
  }
]
```
---
### PUT `/api/messenger/threads/{thread}/participants/{participant}` | *api.messenger.threads.participants.update*
#### Payload:
```json
{
  "add_participants": false,
  "manage_invites": false,
  "send_messages": true,
  "send_knocks": true,
  "start_calls": true
}
```
#### Response:
```json
{
  "id": "923149ab-92bf-405d-826d-95cf222cdb03",
  "admin": false,
  "pending": false,
  "send_knocks": true,
  "send_messages": true,
  "add_participants": false,
  "manage_invites": false,
  "start_calls": true,
  "owner_id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
  "owner_type": "App\\Models\\User",
  "owner": {
    "name": "Jairo O'Kon I",
    "route": null,
    "provider_id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
    "provider_alias": "user",
    "base": {
      "id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
      "name": "Jairo O'Kon I",
      "avatar": null,
      "created_at": "2020-12-07T07:57:15.000000Z",
      "updated_at": "2020-12-08T04:31:44.000000Z"
    },
    "options": {
      "can_message_first": true,
      "friendable": true,
      "can_friend": true,
      "searchable": true,
      "can_search": true,
      "online_status": 0,
      "online_status_verbose": "OFFLINE",
      "friend_status": 1,
      "friend_status_verbose": "FRIEND",
      "last_active": "2020-12-08T04:31:44.000000Z",
      "friend_id": "92313e08-3966-415b-ad71-e6776c813a70"
    },
    "api_avatar": {
      "sm": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
      "md": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
      "lg": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
    },
    "avatar": {
      "sm": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
      "md": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
      "lg": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
    }
  },
  "created_at": "2020-12-08T05:04:30.000000Z",
  "updated_at": "2020-12-08T05:19:40.000000Z",
  "last_read": {
    "time": null,
    "message_id": null
  }
}
```
---
---
### POST `/api/messenger/threads/{thread}/participants/{participant}/promote` | *api.messenger.threads.participants.promote*
#### Payload:
```json
{}
```
#### Response:
```json
{
  "id": "923149ab-92bf-405d-826d-95cf222cdb03",
  "admin": true,
  "pending": false,
  "send_knocks": true,
  "send_messages": true,
  "add_participants": true,
  "manage_invites": true,
  "start_calls": true,
  "owner_id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
  "owner_type": "App\\Models\\User",
  "owner": {
    "name": "Jairo O'Kon I",
    "route": null,
    "provider_id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
    "provider_alias": "user",
    "base": {
      "id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
      "name": "Jairo O'Kon I",
      "avatar": null,
      "created_at": "2020-12-07T07:57:15.000000Z",
      "updated_at": "2020-12-08T04:31:44.000000Z"
    },
    "options": {
      "can_message_first": true,
      "friendable": true,
      "can_friend": true,
      "searchable": true,
      "can_search": true,
      "online_status": 0,
      "online_status_verbose": "OFFLINE",
      "friend_status": 1,
      "friend_status_verbose": "FRIEND",
      "last_active": "2020-12-08T04:31:44.000000Z",
      "friend_id": "92313e08-3966-415b-ad71-e6776c813a70"
    },
    "api_avatar": {
      "sm": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
      "md": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
      "lg": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
    },
    "avatar": {
      "sm": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
      "md": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
      "lg": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
    }
  },
  "created_at": "2020-12-08T05:04:30.000000Z",
  "updated_at": "2020-12-08T05:21:33.000000Z",
  "last_read": {
    "time": null,
    "message_id": null
  }
}
```
---
### POST `/api/messenger/threads/{thread}/participants/{participant}/demote` | *api.messenger.threads.participants.demote*
#### Payload:
```json
{}
```
#### Response:
```json
{
  "id": "923149ab-92bf-405d-826d-95cf222cdb03",
  "admin": false,
  "pending": false,
  "send_knocks": false,
  "send_messages": true,
  "add_participants": false,
  "manage_invites": false,
  "start_calls": false,
  "owner_id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
  "owner_type": "App\\Models\\User",
  "owner": {
    "name": "Jairo O'Kon I",
    "route": null,
    "provider_id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
    "provider_alias": "user",
    "base": {
      "id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
      "name": "Jairo O'Kon I",
      "avatar": null,
      "created_at": "2020-12-07T07:57:15.000000Z",
      "updated_at": "2020-12-08T04:31:44.000000Z"
    },
    "options": {
      "can_message_first": true,
      "friendable": true,
      "can_friend": true,
      "searchable": true,
      "can_search": true,
      "online_status": 0,
      "online_status_verbose": "OFFLINE",
      "friend_status": 1,
      "friend_status_verbose": "FRIEND",
      "last_active": "2020-12-08T04:31:44.000000Z",
      "friend_id": "92313e08-3966-415b-ad71-e6776c813a70"
    },
    "api_avatar": {
      "sm": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
      "md": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
      "lg": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
    },
    "avatar": {
      "sm": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
      "md": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
      "lg": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
    }
  },
  "created_at": "2020-12-08T05:04:30.000000Z",
  "updated_at": "2020-12-08T05:22:50.000000Z",
  "last_read": {
    "time": null,
    "message_id": null
  }
}
```
---
### DELETE `/api/messenger/threads/{thread}/participants/{participant}` | *api.messenger.threads.participants.destroy*
#### Response:
```json
{
  "message": "success"
}
```