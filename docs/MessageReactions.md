### GET `/api/messenger/threads/{thread}/messages/{message}/reactions` | *api.messenger.threads.messages.reactions.index*
#### Response:
```json
{
  "data": {
    ":diamonds:": [
      {
        "id": "9316bf1e-9223-4d62-ab57-441a2da132a1",
        "reaction": ":diamonds:",
        "message_id": "9313f3b4-5238-4bc6-9929-92192d4843f5",
        "created_at": "2021-04-01T06:37:30.141863Z",
        "owner_id": "9313f3b1-f0d1-467f-860c-b8983bdb7984",
        "owner_type": "App\\Models\\User",
        "owner": {
          "name": "Richard Tippin",
          "route": null,
          "provider_id": "9313f3b1-f0d1-467f-860c-b8983bdb7984",
          "provider_alias": "user",
          "base": {
            "id": "9313f3b1-f0d1-467f-860c-b8983bdb7984",
            "name": "Richard Tippin",
            "avatar": null,
            "admin": 1,
            "demo": 0,
            "created_at": "2021-03-30T21:17:01.000000Z",
            "updated_at": "2021-04-02T07:50:35.000000Z"
          },
          "api_avatar": {
            "sm": "/api/messenger/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/sm/default.png",
            "md": "/api/messenger/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/md/default.png",
            "lg": "/api/messenger/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/lg/default.png"
          },
          "avatar": {
            "sm": "/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/sm/default.png",
            "md": "/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/md/default.png",
            "lg": "/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/lg/default.png"
          }
        }
      },
      {
        "id": "9316d5ba-8585-427f-aff7-f1d1a6c29fd7",
        "reaction": ":diamonds:",
        "message_id": "9313f3b4-5238-4bc6-9929-92192d4843f5",
        "created_at": "2021-04-01T07:40:43.334079Z",
        "owner_id": "9313f3b2-43f1-4cc0-99b9-12f050e6aabc",
        "owner_type": "App\\Models\\User",
        "owner": {
          "name": "Magdalena Runolfsson",
          "route": null,
          "provider_id": "9313f3b2-43f1-4cc0-99b9-12f050e6aabc",
          "provider_alias": "user",
          "base": {
            "id": "9313f3b2-43f1-4cc0-99b9-12f050e6aabc",
            "name": "Magdalena Runolfsson",
            "avatar": null,
            "admin": 0,
            "demo": 1,
            "created_at": "2021-03-30T21:17:02.000000Z",
            "updated_at": "2021-04-01T07:52:24.000000Z"
          },
          "api_avatar": {
            "sm": "/api/messenger/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/sm/default.png",
            "md": "/api/messenger/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/md/default.png",
            "lg": "/api/messenger/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/lg/default.png"
          },
          "avatar": {
            "sm": "/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/sm/default.png",
            "md": "/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/md/default.png",
            "lg": "/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/lg/default.png"
          }
        }
      }
    ],
    ":v:": [
      {
        "id": "9316d687-b243-42fb-9af4-049b76dca062",
        "reaction": ":v:",
        "message_id": "9313f3b4-5238-4bc6-9929-92192d4843f5",
        "created_at": "2021-04-01T07:42:57.796819Z",
        "owner_id": "9313f3b2-43f1-4cc0-99b9-12f050e6aabc",
        "owner_type": "App\\Models\\User",
        "owner": {
          "name": "Magdalena Runolfsson",
          "route": null,
          "provider_id": "9313f3b2-43f1-4cc0-99b9-12f050e6aabc",
          "provider_alias": "user",
          "base": {
            "id": "9313f3b2-43f1-4cc0-99b9-12f050e6aabc",
            "name": "Magdalena Runolfsson",
            "avatar": null,
            "admin": 0,
            "demo": 1,
            "created_at": "2021-03-30T21:17:02.000000Z",
            "updated_at": "2021-04-01T07:52:24.000000Z"
          },
          "api_avatar": {
            "sm": "/api/messenger/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/sm/default.png",
            "md": "/api/messenger/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/md/default.png",
            "lg": "/api/messenger/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/lg/default.png"
          },
          "avatar": {
            "sm": "/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/sm/default.png",
            "md": "/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/md/default.png",
            "lg": "/images/user/9313f3b2-43f1-4cc0-99b9-12f050e6aabc/lg/default.png"
          }
        }
      }
    ]
  },
  "meta": {
    "total": 3,
    "total_unique": 2
  }
}
```
---
### POST `/api/messenger/threads/{thread}/messages/{message}/reactions` | *api.messenger.threads.messages.reactions.store*
#### Payload:
```json
{
  "reaction" : "💩"
}
```
#### Response:
```json
{
  "id": "9318dd75-b005-41d3-8757-2df7343fe3ee",
  "reaction": ":poop:",
  "message_id": "9313f3b4-5238-4bc6-9929-92192d4843f5",
  "created_at": "2021-04-02T07:53:59.731813Z",
  "owner_id": "9313f3b1-f0d1-467f-860c-b8983bdb7984",
  "owner_type": "App\\Models\\User",
  "owner": {
    "name": "Richard Tippin",
    "route": null,
    "provider_id": "9313f3b1-f0d1-467f-860c-b8983bdb7984",
    "provider_alias": "user",
    "base": {
      "id": "9313f3b1-f0d1-467f-860c-b8983bdb7984",
      "name": "Richard Tippin",
      "avatar": null,
      "admin": 1,
      "demo": 0,
      "created_at": "2021-03-30T21:17:01.000000Z",
      "updated_at": "2021-04-02T07:52:36.000000Z"
    },
    "api_avatar": {
      "sm": "/api/messenger/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/sm/default.png",
      "md": "/api/messenger/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/md/default.png",
      "lg": "/api/messenger/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/lg/default.png"
    },
    "avatar": {
      "sm": "/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/sm/default.png",
      "md": "/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/md/default.png",
      "lg": "/images/user/9313f3b1-f0d1-467f-860c-b8983bdb7984/lg/default.png"
    }
  }
}
```
---
### DELETE `/api/messenger/threads/{thread}/messages/{message}/reactions/{reaction}` | *api.messenger.threads.messages.reactions.destroy*
#### Response:
```json
{
  "message": "success"
}
```