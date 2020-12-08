### GET `/api/messenger/privates/recipient/{alias}/{id}` | *api.messenger.privates.locate*
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
      "updated_at": "2020-12-08T03:55:36.000000Z"
    },
    "options": {
      "can_message_first": true,
      "friendable": true,
      "can_friend": true,
      "searchable": true,
      "can_search": true,
      "online_status": 1,
      "online_status_verbose": "ONLINE",
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
  },
  "thread_id": null
}
```
---
### POST `/api/messenger/privates` | *api.messenger.privates.store*
#### Payload:
```json
{
  "message": "Hello!",
  "recipient_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "recipient_alias": "user"
}
```
```json
{
  "image": "(binary)",
  "recipient_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "recipient_alias": "user"
}
```
```json
{
  "document": "(binary)",
  "recipient_id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71",
  "recipient_alias": "user"
}
```
#### Response:
```json
{
  "id": "923132b7-97a8-47e0-bad7-05eac51078fd",
  "type": 1,
  "type_verbose": "PRIVATE",
  "has_call": false,
  "locked": false,
  "pending": true,
  "name": "Jane Doe",
  "api_avatar": {
    "sm": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/sm/default.png",
    "md": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/md/default.png",
    "lg": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/lg/default.png"
  },
  "avatar": {
    "sm": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/sm/default.png",
    "md": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/md/default.png",
    "lg": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/lg/default.png"
  },
  "group": false,
  "unread": true,
  "unread_count": 1,
  "created_at": "2020-12-08T04:00:19.000000Z",
  "updated_at": "2020-12-08T04:00:19.000000Z",
  "options": {
    "admin": false,
    "muted": false,
    "add_participants": false,
    "invitations": false,
    "call": false,
    "message": true,
    "knock": false,
    "awaiting_my_approval": false
  },
  "resources": {
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
        "updated_at": "2020-12-08T03:59:36.000000Z"
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
    },
    "latest_message": {
      "id": "923132b7-a0e7-4fb1-bd08-4626b531ad33",
      "thread_id": "923132b7-97a8-47e0-bad7-05eac51078fd",
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
          "updated_at": "2020-12-08T03:59:39.000000Z"
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
      "type": 0,
      "type_verbose": "MESSAGE",
      "system_message": false,
      "body": "Hello!",
      "created_at": "2020-12-08T04:00:19.000000Z",
      "updated_at": "2020-12-08T04:00:19.000000Z",
      "meta": {
        "thread_id": "923132b7-97a8-47e0-bad7-05eac51078fd",
        "thread_type": 1,
        "thread_type_verbose": "PRIVATE"
      }
    }
  }
}
```
---
### POST `/api/messenger/groups` | *api.messenger.groups.store*
#### Payload:
```json
{
  "providers": [
    {
      "alias": "user",
      "id": "922f8476-c5f4-4024-8ba2-1a0d1cd22d71"
    }
  ],
  "subject": "Test Group"
}
```
```json
{
  "providers": null,
  "subject": "Test Group"
}
```
#### Response:
```json
{
  "id": "92313743-a3b3-433f-a2e2-f3ae35d636b0",
  "type": 2,
  "type_verbose": "GROUP",
  "has_call": false,
  "locked": false,
  "pending": false,
  "name": "Test Group",
  "api_avatar": {
    "sm": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/sm/5.png",
    "md": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/md/5.png",
    "lg": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/lg/5.png"
  },
  "avatar": {
    "sm": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/sm/5.png",
    "md": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/md/5.png",
    "lg": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/lg/5.png"
  },
  "group": true,
  "unread": true,
  "unread_count": 1,
  "created_at": "2020-12-08T04:13:02.000000Z",
  "updated_at": "2020-12-08T04:13:02.000000Z",
  "options": {
    "admin": true,
    "muted": false,
    "add_participants": true,
    "invitations": true,
    "call": true,
    "message": true,
    "knock": true
  },
  "resources": {
    "latest_message": {
      "id": "92313743-a7e1-41b9-9336-eb643e58cfd4",
      "thread_id": "92313743-a3b3-433f-a2e2-f3ae35d636b0",
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
          "updated_at": "2020-12-08T04:12:20.000000Z"
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
      "type": 93,
      "type_verbose": "GROUP_CREATED",
      "system_message": true,
      "body": "created Test Group",
      "created_at": "2020-12-08T04:13:02.000000Z",
      "updated_at": "2020-12-08T04:13:02.000000Z",
      "meta": {
        "thread_id": "92313743-a3b3-433f-a2e2-f3ae35d636b0",
        "thread_type": 2,
        "thread_type_verbose": "GROUP",
        "thread_name": "Test Group",
        "api_thread_avatar": {
          "sm": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/sm/5.png",
          "md": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/md/5.png",
          "lg": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/lg/5.png"
        },
        "thread_avatar": {
          "sm": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/sm/5.png",
          "md": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/md/5.png",
          "lg": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/lg/5.png"
        }
      }
    }
  }
}
```
---
### GET `/api/messenger/threads/{thread}` | *api.messenger.threads.show*
#### Response:
```json
{
  "id": "923132b7-97a8-47e0-bad7-05eac51078fd",
  "type": 1,
  "type_verbose": "PRIVATE",
  "has_call": false,
  "locked": false,
  "pending": true,
  "name": "John Doe",
  "api_avatar": {
    "sm": "/api/messenger/images/user/922f8476-bdda-4ebd-b283-be23602c658d/sm/img_5fcee7c1e64404.55920965.jpg",
    "md": "/api/messenger/images/user/922f8476-bdda-4ebd-b283-be23602c658d/md/img_5fcee7c1e64404.55920965.jpg",
    "lg": "/api/messenger/images/user/922f8476-bdda-4ebd-b283-be23602c658d/lg/img_5fcee7c1e64404.55920965.jpg"
  },
  "avatar": {
    "sm": "/images/user/922f8476-bdda-4ebd-b283-be23602c658d/sm/img_5fcee7c1e64404.55920965.jpg",
    "md": "/images/user/922f8476-bdda-4ebd-b283-be23602c658d/md/img_5fcee7c1e64404.55920965.jpg",
    "lg": "/images/user/922f8476-bdda-4ebd-b283-be23602c658d/lg/img_5fcee7c1e64404.55920965.jpg"
  },
  "group": false,
  "unread": true,
  "unread_count": 1,
  "created_at": "2020-12-08T04:00:19.000000Z",
  "updated_at": "2020-12-08T04:00:19.000000Z",
  "options": {
    "admin": false,
    "muted": false,
    "add_participants": false,
    "invitations": false,
    "call": false,
    "message": false,
    "knock": false,
    "awaiting_my_approval": true
  },
  "resources": {
    "recipient": {
      "name": "John Doe",
      "route": null,
      "provider_id": "922f8476-bdda-4ebd-b283-be23602c658d",
      "provider_alias": "user",
      "base": {
        "id": "922f8476-bdda-4ebd-b283-be23602c658d",
        "name": "John Doe",
        "avatar": "img_5fcee7c1e64404.55920965.jpg",
        "created_at": "2020-12-07T07:57:14.000000Z",
        "updated_at": "2020-12-08T04:03:40.000000Z"
      },
      "options": {
        "can_message_first": true,
        "friendable": true,
        "can_friend": true,
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
    "latest_message": {
      "id": "923132b7-a0e7-4fb1-bd08-4626b531ad33",
      "thread_id": "923132b7-97a8-47e0-bad7-05eac51078fd",
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
          "updated_at": "2020-12-08T04:03:40.000000Z"
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
      "type": 0,
      "type_verbose": "MESSAGE",
      "system_message": false,
      "body": "Hello!",
      "created_at": "2020-12-08T04:00:19.000000Z",
      "updated_at": "2020-12-08T04:00:19.000000Z",
      "meta": {
        "thread_id": "923132b7-97a8-47e0-bad7-05eac51078fd",
        "thread_type": 1,
        "thread_type_verbose": "PRIVATE"
      }
    }
  }
}
```
---
### POST `/api/messenger/threads/{thread}/approval` | *api.messenger.threads.approval*
#### Payload:
```json
{
    "approve" : true
}
```
#### Response:
```json
{
  "message" : "success"
}
```
---
### GET `/api/messenger/threads` | *api.messenger.threads.index*
### GET `/api/messenger/privates` | *api.messenger.privates.index*
### GET `/api/messenger/groups` | *api.messenger.groups.index*
#### Response:
```json
{
  "data": [
    {
      "id": "92313743-a3b3-433f-a2e2-f3ae35d636b0",
      "type": 2,
      "type_verbose": "GROUP",
      "has_call": false,
      "locked": false,
      "pending": false,
      "name": "Test Group",
      "api_avatar": {
        "sm": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/sm/5.png",
        "md": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/md/5.png",
        "lg": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/lg/5.png"
      },
      "avatar": {
        "sm": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/sm/5.png",
        "md": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/md/5.png",
        "lg": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/lg/5.png"
      },
      "group": true,
      "unread": false,
      "unread_count": 0,
      "created_at": "2020-12-08T04:13:02.000000Z",
      "updated_at": "2020-12-08T04:13:03.000000Z",
      "options": {
        "admin": true,
        "muted": false,
        "add_participants": true,
        "invitations": true,
        "call": true,
        "message": true,
        "knock": true
      },
      "resources": {
        "latest_message": {
          "id": "92313745-5db7-45d5-8087-a1967f98258f",
          "thread_id": "92313743-a3b3-433f-a2e2-f3ae35d636b0",
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
              "updated_at": "2020-12-08T04:15:07.000000Z"
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
          "type": 99,
          "type_verbose": "PARTICIPANTS_ADDED",
          "system_message": true,
          "body": "added Jane Doe to the group",
          "created_at": "2020-12-08T04:13:03.000000Z",
          "updated_at": "2020-12-08T04:13:03.000000Z",
          "meta": {
            "thread_id": "92313743-a3b3-433f-a2e2-f3ae35d636b0",
            "thread_type": 2,
            "thread_type_verbose": "GROUP",
            "thread_name": "Test Group",
            "api_thread_avatar": {
              "sm": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/sm/5.png",
              "md": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/md/5.png",
              "lg": "/api/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/lg/5.png"
            },
            "thread_avatar": {
              "sm": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/sm/5.png",
              "md": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/md/5.png",
              "lg": "/messenger/threads/92313743-a3b3-433f-a2e2-f3ae35d636b0/avatar/lg/5.png"
            }
          }
        }
      }
    },
    {
      "id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
      "type": 1,
      "type_verbose": "PRIVATE",
      "has_call": false,
      "locked": false,
      "pending": false,
      "name": "Jane Doe",
      "api_avatar": {
        "sm": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/sm/default.png",
        "md": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/md/default.png",
        "lg": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/lg/default.png"
      },
      "avatar": {
        "sm": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/sm/default.png",
        "md": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/md/default.png",
        "lg": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/lg/default.png"
      },
      "group": false,
      "unread": false,
      "unread_count": 0,
      "created_at": "2020-12-08T04:09:01.000000Z",
      "updated_at": "2020-12-08T04:09:01.000000Z",
      "options": {
        "admin": false,
        "muted": false,
        "add_participants": false,
        "invitations": false,
        "call": true,
        "message": true,
        "knock": true
      },
      "resources": {
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
            "updated_at": "2020-12-08T04:13:36.000000Z"
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
            "last_active": "2020-12-08T04:13:36.000000Z",
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
        "latest_message": {
          "id": "923135d4-c478-46aa-aed3-7013d9609e1d",
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
              "updated_at": "2020-12-08T04:15:07.000000Z"
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
          "type": 0,
          "type_verbose": "MESSAGE",
          "system_message": false,
          "body": "Hello!",
          "created_at": "2020-12-08T04:09:01.000000Z",
          "updated_at": "2020-12-08T04:09:01.000000Z",
          "meta": {
            "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
            "thread_type": 1,
            "thread_type_verbose": "PRIVATE"
          }
        }
      }
    }
  ],
  "meta": {
    "index": true,
    "page_id": null,
    "next_page_id": null,
    "next_page_route": null,
    "final_page": true,
    "per_page": 100,
    "results": 2,
    "total": 2
  }
}
```
---
### GET `/api/messenger/threads/{thread}/check-archive` | *api.messenger.threads.archive.check*
#### Response:
```json
{
    "name": "Test Group",
    "group": true,
    "created_at": "2020-12-08T04:13:02.000000Z",
    "messages_count": 2,
    "participants_count": 2,
    "calls_count": 0
}
```
---
### DELETE `/api/messenger/threads/{thread}` | *api.messenger.threads.destroy*
#### Response:
```json
{
    "message": "success"
}
```
---
### POST `/api/messenger/threads/{thread}/knock-knock` | *api.messenger.threads.knock*
#### Payload:
```json
{}
```
#### Response:
```json
{
    "message": "success"
}
```
---
### GET `/api/messenger/threads/{thread}/is-unread` | *api.messenger.threads.is.unread*
#### Response:
```json
{
    "unread": false
}
```
---
### GET `/api/messenger/threads/{thread}/mark-read` | *api.messenger.threads.mark.read*
#### Response:
```json
{
    "message": "success"
}
```
---
### GET `/api/messenger/threads/{thread}/logs` | *api.messenger.threads.logs*
#### Response:
```json
{
  "data": [
    {
      "id": "92313cfa-f190-4b95-8980-199c841623eb",
      "thread_id": "92313cf7-b356-4d74-944c-c799fcfc3b1e",
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
          "updated_at": "2020-12-08T04:29:08.000000Z"
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
      "type": 99,
      "type_verbose": "PARTICIPANTS_ADDED",
      "system_message": true,
      "body": "added Jane Doe to the group",
      "created_at": "2020-12-08T04:29:01.000000Z",
      "updated_at": "2020-12-08T04:29:01.000000Z",
      "meta": {
        "thread_id": "92313cf7-b356-4d74-944c-c799fcfc3b1e",
        "thread_type": 2,
        "thread_type_verbose": "GROUP",
        "thread_name": "Test Group!",
        "api_thread_avatar": {
          "sm": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/sm/2.png",
          "md": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/md/2.png",
          "lg": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/lg/2.png"
        },
        "thread_avatar": {
          "sm": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/sm/2.png",
          "md": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/md/2.png",
          "lg": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/lg/2.png"
        }
      }
    },
    {
      "id": "92313cf7-b92e-4c23-9601-b1b5be16a42d",
      "thread_id": "92313cf7-b356-4d74-944c-c799fcfc3b1e",
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
          "updated_at": "2020-12-08T04:29:08.000000Z"
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
      "type": 93,
      "type_verbose": "GROUP_CREATED",
      "system_message": true,
      "body": "created Test Group!",
      "created_at": "2020-12-08T04:28:59.000000Z",
      "updated_at": "2020-12-08T04:28:59.000000Z",
      "meta": {
        "thread_id": "92313cf7-b356-4d74-944c-c799fcfc3b1e",
        "thread_type": 2,
        "thread_type_verbose": "GROUP",
        "thread_name": "Test Group!",
        "api_thread_avatar": {
          "sm": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/sm/2.png",
          "md": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/md/2.png",
          "lg": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/lg/2.png"
        },
        "thread_avatar": {
          "sm": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/sm/2.png",
          "md": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/md/2.png",
          "lg": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/lg/2.png"
        }
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
    "results": 2,
    "total": 2
  }
}
```
---
### GET `/api/messenger/threads/{thread}/add-participants` | *api.messenger.threads.add.participants*
#### Response:
```json
[
    {
        "party": {
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
                "online_status": 1,
                "online_status_verbose": "ONLINE",
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
        "type_verbose": "FRIEND",
        "id": "92313e08-3966-415b-ad71-e6776c813a70",
        "owner_type": "App\\Models\\User",
        "owner_id": "922f8476-bdda-4ebd-b283-be23602c658d",
        "party_type": "App\\Models\\User",
        "party_id": "922f8476-dfb5-45d5-8a76-ab6422e10e5d",
        "created_at": "2020-12-08T04:31:57.000000Z",
        "updated_at": "2020-12-08T04:31:57.000000Z"
    }
]
```
---
### POST `/api/messenger/threads/{thread}/leave` | *api.messenger.threads.leave*
#### Payload:
```json
{}
```
#### Response:
```json
{
  "message": "success"
}
```
---
### GET `/api/messenger/threads/{thread}/settings` | *api.messenger.threads.settings*
#### Response:
```json
{
  "name": "Test Group!",
  "api_avatar": {
    "sm": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/sm/2.png",
    "md": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/md/2.png",
    "lg": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/lg/2.png"
  },
  "avatar": {
    "sm": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/sm/2.png",
    "md": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/md/2.png",
    "lg": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/lg/2.png"
  },
  "add_participants": true,
  "invitations": true,
  "calling": true,
  "messaging": true,
  "knocks": true
}
```
---
### PUT `/api/messenger/threads/{thread}/settings` | *api.messenger.threads.settings.update*
#### Payload:
```json
{
  "subject": "Test Party!",
  "add_participants": false,
  "invitations": true,
  "calling": true,
  "messaging": true,
  "knocks": true
}
```
#### Response:
```json
{
  "name": "Test Party!",
  "api_avatar": {
    "sm": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/sm/2.png",
    "md": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/md/2.png",
    "lg": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/lg/2.png"
  },
  "avatar": {
    "sm": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/sm/2.png",
    "md": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/md/2.png",
    "lg": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/lg/2.png"
  },
  "add_participants": false,
  "invitations": true,
  "calling": true,
  "messaging": true,
  "knocks": true
}
```
---
### POST `/api/messenger/threads/{thread}/avatar` | *api.messenger.threads.avatar.update*
#### Payload:
```json
{
  "default": "3.png"
}
```
```json
{
  "image": "(binary)"
}
```
#### Response:
```json
{
  "name": "Test Party!",
  "api_avatar": {
    "sm": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/sm/3.png",
    "md": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/md/3.png",
    "lg": "/api/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/lg/3.png"
  },
  "avatar": {
    "sm": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/sm/3.png",
    "md": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/md/3.png",
    "lg": "/messenger/threads/92313cf7-b356-4d74-944c-c799fcfc3b1e/avatar/lg/3.png"
  },
  "add_participants": false,
  "invitations": true,
  "calling": true,
  "messaging": true,
  "knocks": true
}
```
---
### GET `/api/messenger/threads/{thread}/avatar/{size}/{image}` | *api.messenger.threads.avatar.render*
#### Response:
```
Renders Group Avatar
```
---
### GET `/api/messenger/threads/{thread}/load/messages|participants|mark-read` | *api.messenger.threads.loader*
#### Response:
```json
{
    "id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
    "type": 1,
    "type_verbose": "PRIVATE",
    "has_call": false,
    "locked": false,
    "pending": false,
    "name": "Jane Doe",
    "api_avatar": {
        "sm": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/sm/default.png",
        "md": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/md/default.png",
        "lg": "/api/messenger/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/lg/default.png"
    },
    "avatar": {
        "sm": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/sm/default.png",
        "md": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/md/default.png",
        "lg": "/images/user/922f8476-c5f4-4024-8ba2-1a0d1cd22d71/lg/default.png"
    },
    "group": false,
    "unread": false,
    "unread_count": 0,
    "created_at": "2020-12-08T04:09:01.000000Z",
    "updated_at": "2020-12-08T04:47:41.000000Z",
    "options": {
        "admin": false,
        "muted": false,
        "add_participants": false,
        "invitations": false,
        "call": true,
        "message": true,
        "knock": true
    },
    "resources": {
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
                "updated_at": "2020-12-08T04:47:53.000000Z"
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
                "last_active": "2020-12-08T04:47:53.000000Z",
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
        "participants": {
            "data": [
                {
                    "id": "923135d4-c0f9-417d-ad5b-ac7812db1553",
                    "admin": false,
                    "pending": false,
                    "send_knocks": false,
                    "send_messages": true,
                    "add_participants": false,
                    "manage_invites": 0,
                    "start_calls": false,
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
                            "updated_at": "2020-12-08T04:47:42.000000Z"
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
                    "created_at": "2020-12-08T04:09:01.000000Z",
                    "updated_at": "2020-12-08T04:47:48.000000Z",
                    "last_read": {
                        "time": "2020-12-08T04:47:48.000000Z",
                        "message_id": "923143a8-aa8e-46d7-a601-b55c1d925aad"
                    }
                },
                {
                    "id": "923135d4-c1ae-422d-9ce6-88f8dfc6a822",
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
                            "updated_at": "2020-12-08T04:47:53.000000Z"
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
                            "last_active": "2020-12-08T04:47:53.000000Z",
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
                    "created_at": "2020-12-08T04:09:01.000000Z",
                    "updated_at": "2020-12-08T04:47:42.000000Z",
                    "last_read": {
                        "time": "2020-12-08T04:47:42.000000Z",
                        "message_id": "923143a8-aa8e-46d7-a601-b55c1d925aad"
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
        },
        "messages": {
            "data": [
                {
                    "id": "923143a8-aa8e-46d7-a601-b55c1d925aad",
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
                            "updated_at": "2020-12-08T04:47:53.000000Z"
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
                    "type": 90,
                    "type_verbose": "VIDEO_CALL",
                    "system_message": true,
                    "body": "was in a video call",
                    "created_at": "2020-12-08T04:47:41.000000Z",
                    "updated_at": "2020-12-08T04:47:41.000000Z",
                    "meta": {
                        "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
                        "thread_type": 1,
                        "thread_type_verbose": "PRIVATE"
                    }
                },
                {
                    "id": "923135d4-c478-46aa-aed3-7013d9609e1d",
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
                            "updated_at": "2020-12-08T04:47:42.000000Z"
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
                    "type": 0,
                    "type_verbose": "MESSAGE",
                    "system_message": false,
                    "body": "Hello!",
                    "created_at": "2020-12-08T04:09:01.000000Z",
                    "updated_at": "2020-12-08T04:09:01.000000Z",
                    "meta": {
                        "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
                        "thread_type": 1,
                        "thread_type_verbose": "PRIVATE"
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
                "results": 2,
                "total": 2
            }
        },
        "latest_message": {
            "id": "923143a8-aa8e-46d7-a601-b55c1d925aad",
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
                    "updated_at": "2020-12-08T04:47:53.000000Z"
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
            "type": 90,
            "type_verbose": "VIDEO_CALL",
            "system_message": true,
            "body": "was in a video call",
            "created_at": "2020-12-08T04:47:41.000000Z",
            "updated_at": "2020-12-08T04:47:41.000000Z",
            "meta": {
                "thread_id": "923135d4-bcaa-4aa7-83a9-cc9765866b19",
                "thread_type": 1,
                "thread_type_verbose": "PRIVATE"
            }
        }
    }
}
```