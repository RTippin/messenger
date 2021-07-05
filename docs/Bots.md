### POST `/api/messenger/threads/{thread}/bots` | *api.messenger.threads.bots.store*
#### Payload:
```json
{
  "name": "Mr. Bot",
  "cooldown": 0,
  "enabled": true,
  "hide_actions": false,
}
```
#### Response:
```json
{
  "id": "93d58c5a-8637-4b29-9fef-fec7c0595b4b",
  "thread_id": "93d58908-9a42-492c-a3b9-579276505aba",
  "owner_id": "93d588ab-4934-4edb-8a51-7a3f2e24bc10",
  "owner_type": "users",
  "owner": {
    "name": "Richard Tippin",
    "route": null,
    "provider_id": "93d588ab-4934-4edb-8a51-7a3f2e24bc10",
    "provider_alias": "user",
    "base": {
      "id": "93d588ab-4934-4edb-8a51-7a3f2e24bc10",
      "name": "Richard Tippin",
      "avatar": "img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "admin": 1,
      "demo": 0,
      "created_at": "2021-07-05T02:48:22.000000Z",
      "updated_at": "2021-07-05T02:57:07.000000Z"
    },
    "avatar": {
      "sm": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/sm/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "md": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/md/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "lg": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/lg/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg"
    }
  },
  "created_at": "2021-07-05T02:58:40.000000Z",
  "updated_at": "2021-07-05T02:58:40.000000Z",
  "name": "Mr. Bot",
  "enabled": true,
  "hide_actions": false,
  "cooldown": 0,
  "on_cooldown": false,
  "actions_count": 0,
  "avatar": {
    "sm": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/sm/default.png",
    "md": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/md/default.png",
    "lg": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/lg/default.png"
  }
}
```
---
### POST `/api/messenger/threads/{thread}/bots/{bot}/actions` | *api.messenger.threads.bots.actions.store*
#### Payload:
```json
{
  "handler": "reply",
  "cooldown": 0,
  "enabled": true,
  "admin_only": false,
  "triggers": [
    "!test"
  ],
  "match": "exact",
  "quote_original": false,
  "replies": [
    "This is a test."
  ]
}
```
#### Response:
```json
{
  "id": "93d58e00-017b-4c9a-8c77-8cb4de2569bc",
  "bot_id": "93d58c5a-8637-4b29-9fef-fec7c0595b4b",
  "owner_id": "93d588ab-4934-4edb-8a51-7a3f2e24bc10",
  "owner_type": "users",
  "created_at": "2021-07-05T03:03:16.000000Z",
  "updated_at": "2021-07-05T03:03:16.000000Z",
  "enabled": true,
  "admin_only": false,
  "cooldown": 0,
  "on_cooldown": false,
  "match": "exact",
  "match_description": "The trigger must match the message exactly.",
  "triggers": [
    "!test"
  ],
  "payload": {
    "replies": [
      "This is a test."
    ],
    "quote_original": false
  },
  "handler": {
    "alias": "reply",
    "description": "Reply with the given response(s).",
    "name": "Reply",
    "unique": false,
    "authorize": false,
    "triggers": null,
    "match": null
  },
  "owner": {
    "name": "Richard Tippin",
    "route": null,
    "provider_id": "93d588ab-4934-4edb-8a51-7a3f2e24bc10",
    "provider_alias": "user",
    "base": {
      "id": "93d588ab-4934-4edb-8a51-7a3f2e24bc10",
      "name": "Richard Tippin",
      "avatar": "img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "admin": 1,
      "demo": 0,
      "created_at": "2021-07-05T02:48:22.000000Z",
      "updated_at": "2021-07-05T03:03:05.000000Z"
    },
    "avatar": {
      "sm": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/sm/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "md": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/md/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "lg": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/lg/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg"
    }
  }
}
```