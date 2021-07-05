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
### GET `/api/messenger/threads/{thread}/bots` | *api.messenger.threads.bots.index*
#### Response:
```json
[
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
        "updated_at": "2021-07-05T03:22:12.000000Z"
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
    "actions_count": 1,
    "avatar": {
      "sm": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/sm/default.png",
      "md": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/md/default.png",
      "lg": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/lg/default.png"
    }
  }
]
```
---
### GET `/api/messenger/threads/{thread}/bots/{bot}` | *api.messenger.threads.bots.show*
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
      "updated_at": "2021-07-05T03:22:12.000000Z"
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
  "actions": [
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
          "updated_at": "2021-07-05T03:22:12.000000Z"
        },
        "avatar": {
          "sm": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/sm/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
          "md": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/md/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
          "lg": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/lg/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg"
        }
      }
    }
  ],
  "avatar": {
    "sm": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/sm/default.png",
    "md": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/md/default.png",
    "lg": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/lg/default.png"
  }
}
```
---
### PUT `/api/messenger/threads/{thread}/bots/{bot}` | *api.messenger.threads.bots.update*
#### Payload:
```json
{
  "name": "Mr. Botty",
  "enabled": true,
  "hide_actions": false,
  "cooldown": 10
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
      "updated_at": "2021-07-05T03:39:07.000000Z"
    },
    "avatar": {
      "sm": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/sm/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "md": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/md/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "lg": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/lg/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg"
    }
  },
  "created_at": "2021-07-05T02:58:40.000000Z",
  "updated_at": "2021-07-05T03:40:17.000000Z",
  "name": "Mr. Botty",
  "enabled": true,
  "hide_actions": false,
  "cooldown": 10,
  "on_cooldown": false,
  "actions_count": 1,
  "avatar": {
    "sm": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/sm/default.png",
    "md": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/md/default.png",
    "lg": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/lg/default.png"
  }
}
```
---
### DELETE `/api/messenger/threads/{thread}/bots/{bot}` | *api.messenger.threads.bots.destroy*
#### Response:
```json
{
  "message": "success"
}
```
---
### POST `/api/messenger/threads/{thread}/bots/{bot}/avatar` | *api.messenger.threads.bots.avatar.store*
#### Payload:
```json
{
  "image": "(binary)"
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
      "updated_at": "2021-07-05T03:49:05.000000Z"
    },
    "avatar": {
      "sm": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/sm/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "md": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/md/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "lg": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/lg/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg"
    }
  },
  "created_at": "2021-07-05T02:58:40.000000Z",
  "updated_at": "2021-07-05T03:50:07.000000Z",
  "name": "Mr. Botty",
  "enabled": true,
  "hide_actions": false,
  "cooldown": 10,
  "on_cooldown": false,
  "actions_count": 1,
  "avatar": {
    "sm": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/sm/img_d0a9dfca-21a5-4373-bb27-8752de8cdae0.jpg",
    "md": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/md/img_d0a9dfca-21a5-4373-bb27-8752de8cdae0.jpg",
    "lg": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/lg/img_d0a9dfca-21a5-4373-bb27-8752de8cdae0.jpg"
  }
}
```
---
### DELETE `/api/messenger/threads/{thread}/bots/{bot}/avatar` | *api.messenger.threads.bots.avatar.destroy*
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
      "updated_at": "2021-07-05T03:51:06.000000Z"
    },
    "avatar": {
      "sm": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/sm/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "md": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/md/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "lg": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/lg/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg"
    }
  },
  "created_at": "2021-07-05T02:58:40.000000Z",
  "updated_at": "2021-07-05T03:51:17.000000Z",
  "name": "Mr. Botty",
  "enabled": true,
  "hide_actions": false,
  "cooldown": 10,
  "on_cooldown": false,
  "actions_count": 1,
  "avatar": {
    "sm": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/sm/default.png",
    "md": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/md/default.png",
    "lg": "/messenger/assets/threads/93d58908-9a42-492c-a3b9-579276505aba/bots/93d58c5a-8637-4b29-9fef-fec7c0595b4b/avatar/lg/default.png"
  }
}
```
---
### GET `/api/messenger/threads/{thread}/bots/{bot}/add-handlers` | *api.messenger.threads.bots.handlers*
#### Response:
```json
[
  {
    "alias": "react",
    "description": "Adds the specified reaction to a message.",
    "name": "Reaction",
    "unique": false,
    "authorize": false,
    "triggers": null,
    "match": null
  },
  {
    "alias": "reply",
    "description": "Reply with the given response(s).",
    "name": "Reply",
    "unique": false,
    "authorize": false,
    "triggers": null,
    "match": null
  },
  {
    "alias": "rock_paper_scissors",
    "description": "Play a quick game of rock, paper, scissors! [ !rps {rock|paper|scissors} ]",
    "name": "Rock Paper Scissors",
    "unique": true,
    "authorize": false,
    "triggers": [
      "!rps"
    ],
    "match": "starts:with:caseless"
  },
  {
    "alias": "weather",
    "description": "Get the current weather for the given location. [ !w {location} ]",
    "name": "Weather",
    "unique": true,
    "authorize": false,
    "triggers": [
      "!w",
      "!weather"
    ],
    "match": "starts:with:caseless"
  }
]
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
---
### GET `/api/messenger/threads/{thread}/bots/{bot}/actions` | *api.messenger.threads.bots.actions.index*
#### Response:
```json
[
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
        "updated_at": "2021-07-05T03:57:06.000000Z"
      },
      "avatar": {
        "sm": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/sm/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
        "md": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/md/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
        "lg": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/lg/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg"
      }
    }
  },
  {
    "id": "93d5a195-1966-4833-8e53-0c65ebdcac4b",
    "bot_id": "93d58c5a-8637-4b29-9fef-fec7c0595b4b",
    "owner_id": "93d588ab-4934-4edb-8a51-7a3f2e24bc10",
    "owner_type": "users",
    "created_at": "2021-07-05T03:58:02.000000Z",
    "updated_at": "2021-07-05T03:58:02.000000Z",
    "enabled": true,
    "admin_only": false,
    "cooldown": 15,
    "on_cooldown": false,
    "match": "starts:with:caseless",
    "match_description": "Same as \"starts with\", but is case insensitive.",
    "triggers": [
      "!rps"
    ],
    "payload": null,
    "handler": {
      "alias": "rock_paper_scissors",
      "description": "Play a quick game of rock, paper, scissors! [ !rps {rock|paper|scissors} ]",
      "name": "Rock Paper Scissors",
      "unique": true,
      "authorize": false,
      "triggers": [
        "!rps"
      ],
      "match": "starts:with:caseless"
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
        "updated_at": "2021-07-05T03:57:06.000000Z"
      },
      "avatar": {
        "sm": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/sm/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
        "md": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/md/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
        "lg": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/lg/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg"
      }
    }
  }
]
```
---
### GET `/api/messenger/threads/{thread}/bots/{bot}/actions/{action}` | *api.messenger.threads.bots.actions.show*
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
      "updated_at": "2021-07-05T04:01:06.000000Z"
    },
    "avatar": {
      "sm": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/sm/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "md": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/md/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "lg": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/lg/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg"
    }
  }
}
```
---
### PUT `/api/messenger/threads/{thread}/bots/{bot}/actions/{action}` | *api.messenger.threads.bots.actions.update*
#### Payload:
```json
{
  "handler": "reply",
  "cooldown": 20,
  "enabled": true,
  "admin_only": false,
  "triggers": [
    "!test,!more"
  ],
  "match": "exact",
  "quote_original": false,
  "replies": [
    "This is a test.",
    "Another reply."
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
  "updated_at": "2021-07-05T04:04:03.000000Z",
  "enabled": true,
  "admin_only": false,
  "cooldown": "20",
  "on_cooldown": false,
  "match": "exact",
  "match_description": "The trigger must match the message exactly.",
  "triggers": [
    "!test",
    "!more"
  ],
  "payload": {
    "replies": [
      "This is a test.",
      "Another reply."
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
      "updated_at": "2021-07-05T04:03:06.000000Z"
    },
    "avatar": {
      "sm": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/sm/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "md": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/md/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg",
      "lg": "/messenger/assets/provider/user/93d588ab-4934-4edb-8a51-7a3f2e24bc10/lg/img_4abd43e7-3cee-4c55-8452-1224099ebd7e.jpg"
    }
  }
}
```
---
### DELETE `/api/messenger/threads/{thread}/bots/{bot}/actions/{action}` | *api.messenger.threads.bots.actions.destroy*
#### Response:
```json
{
  "message": "success"
}
```
