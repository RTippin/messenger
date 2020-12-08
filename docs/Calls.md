### POST `/api/messenger/threads/{thread}/calls` | *api.messenger.threads.calls.store*
#### Payload:
```json
{}
```
#### Response:
```json
{
  "id": "9231616a-43ad-4232-ba58-0d79f0b89871",
  "active": true,
  "type": 1,
  "type_verbose": "VIDEO",
  "thread_id": "92316158-d218-4b23-b90b-a708fb354685",
  "created_at": "2020-12-08T06:10:54.000000Z",
  "updated_at": "2020-12-08T06:10:54.000000Z",
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
      "updated_at": "2020-12-08T06:10:43.000000Z"
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
  "meta": {
    "thread_id": "92316158-d218-4b23-b90b-a708fb354685",
    "thread_type": 1,
    "thread_type_verbose": "PRIVATE",
    "thread_name": "Jairo O'Kon I",
    "api_thread_avatar": {
      "sm": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
      "md": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
      "lg": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
    },
    "thread_avatar": {
      "sm": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
      "md": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
      "lg": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
    }
  },
  "options": {
    "admin": false,
    "setup_complete": false,
    "in_call": false,
    "left_call": false,
    "joined": false,
    "kicked": false
  }
}
```
---
### POST `/api/messenger/threads/{thread}/calls/{call}/join` | *api.messenger.threads.calls.join*
#### Payload:
```json
{}
```
#### Response:
```json
{
  "id": "9231622d-6f3d-46cd-8735-596994e2dd3a",
  "call_id": "9231616a-43ad-4232-ba58-0d79f0b89871",
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
      "updated_at": "2020-12-08T06:11:33.000000Z"
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
  "created_at": "2020-12-08T06:13:01.000000Z",
  "updated_at": "2020-12-08T06:13:01.000000Z",
  "left_call": null
}
```
---
### POST `/api/messenger/threads/{thread}/calls/{call}/leave` | *api.messenger.threads.calls.leave*
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
### GET `/api/messenger/threads/{thread}/calls/{call}` | *api.messenger.threads.calls.show*
#### Response:
```json
{
  "id": "9231616a-43ad-4232-ba58-0d79f0b89871",
  "active": true,
  "type": 1,
  "type_verbose": "VIDEO",
  "thread_id": "92316158-d218-4b23-b90b-a708fb354685",
  "created_at": "2020-12-08T06:10:54.000000Z",
  "updated_at": "2020-12-08T06:10:55.000000Z",
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
      "updated_at": "2020-12-08T06:15:40.000000Z"
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
  "meta": {
    "thread_id": "92316158-d218-4b23-b90b-a708fb354685",
    "thread_type": 1,
    "thread_type_verbose": "PRIVATE",
    "thread_name": "Jairo O'Kon I",
    "api_thread_avatar": {
      "sm": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
      "md": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
      "lg": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
    },
    "thread_avatar": {
      "sm": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
      "md": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
      "lg": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
    }
  },
  "options": {
    "admin": true,
    "setup_complete": true,
    "in_call": true,
    "left_call": false,
    "joined": true,
    "kicked": false,
    "room_id": 4883964242392592,
    "room_pin": "k5dBub",
    "payload": null
  }
}
```
---
### GET `/api/messenger/threads/{thread}/calls/{call}/heartbeat` | *api.messenger.threads.calls.heartbeat*
#### Response:
```json
{
  "message": "success"
}
```
---
### POST `/api/messenger/threads/{thread}/calls/{call}/end` | *api.messenger.threads.calls.end*
#### Response:
```json
{
  "message": "success"
}
```
---
### GET `/api/messenger/threads/{thread}/calls` | *api.messenger.threads.calls.index*
#### Response:
```json
{
  "data": [
    {
      "id": "9231648d-80d8-4ccb-94cc-146401610a45",
      "active": true,
      "type": 1,
      "type_verbose": "VIDEO",
      "thread_id": "92316158-d218-4b23-b90b-a708fb354685",
      "created_at": "2020-12-08T06:19:40.000000Z",
      "updated_at": "2020-12-08T06:19:40.000000Z",
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
          "updated_at": "2020-12-08T06:19:08.000000Z"
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
      "meta": {
        "thread_id": "92316158-d218-4b23-b90b-a708fb354685",
        "thread_type": 1,
        "thread_type_verbose": "PRIVATE",
        "thread_name": "Jairo O'Kon I",
        "api_thread_avatar": {
          "sm": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
          "md": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
          "lg": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
        },
        "thread_avatar": {
          "sm": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
          "md": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
          "lg": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
        }
      },
      "options": {
        "admin": true,
        "setup_complete": true,
        "in_call": true,
        "left_call": false,
        "joined": true,
        "kicked": false,
        "room_id": 2090903900742574,
        "room_pin": "31aP4O",
        "payload": null
      }
    },
    {
      "id": "9231616a-43ad-4232-ba58-0d79f0b89871",
      "active": false,
      "type": 1,
      "type_verbose": "VIDEO",
      "thread_id": "92316158-d218-4b23-b90b-a708fb354685",
      "created_at": "2020-12-08T06:10:54.000000Z",
      "updated_at": "2020-12-08T06:18:36.000000Z",
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
          "updated_at": "2020-12-08T06:19:08.000000Z"
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
      "meta": {
        "thread_id": "92316158-d218-4b23-b90b-a708fb354685",
        "thread_type": 1,
        "thread_type_verbose": "PRIVATE",
        "thread_name": "Jairo O'Kon I",
        "api_thread_avatar": {
          "sm": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
          "md": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
          "lg": "/api/messenger/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
        },
        "thread_avatar": {
          "sm": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/sm/default.png",
          "md": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/md/default.png",
          "lg": "/images/user/922f8476-dfb5-45d5-8a76-ab6422e10e5d/lg/default.png"
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
    "per_page": 25,
    "results": 2,
    "total": 2
  }
}
```
---
### GET `/api/messenger/threads/{thread}/calls/{call}/participants` | *api.messenger.threads.calls.participants.index*
#### Response:
```json
[
  {
    "id": "9231648d-8586-4d04-8cf2-beec32e3ce8c",
    "call_id": "9231648d-80d8-4ccb-94cc-146401610a45",
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
        "updated_at": "2020-12-08T06:21:11.000000Z"
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
    "created_at": "2020-12-08T06:19:40.000000Z",
    "updated_at": "2020-12-08T06:19:40.000000Z",
    "left_call": null
  },
  {
    "id": "9231650a-6e26-43d7-9543-9fe21809db3c",
    "call_id": "9231648d-80d8-4ccb-94cc-146401610a45",
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
        "updated_at": "2020-12-08T06:21:34.000000Z"
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
        "last_active": "2020-12-08T06:21:34.000000Z",
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
    "created_at": "2020-12-08T06:21:02.000000Z",
    "updated_at": "2020-12-08T06:21:02.000000Z",
    "left_call": null
  }
]
```
---
### GET `/api/messenger/threads/{thread}/calls/{call}/participants/{participant}` | *api.messenger.threads.calls.participants.show*
#### Response:
```json
{
  "id": "9231650a-6e26-43d7-9543-9fe21809db3c",
  "call_id": "9231648d-80d8-4ccb-94cc-146401610a45",
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
      "updated_at": "2020-12-08T06:23:34.000000Z"
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
      "last_active": "2020-12-08T06:23:34.000000Z",
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
  "created_at": "2020-12-08T06:21:02.000000Z",
  "updated_at": "2020-12-08T06:21:02.000000Z",
  "left_call": null
}
```
---
### PUT `/api/messenger/threads/{thread}/calls/{call}/participants/{participant}` | *api.messenger.threads.calls.participants.update*
#### Payload:
```json
{
  "kicked": true
}
```
#### Response:
```json
{
  "message": "success"
}
```