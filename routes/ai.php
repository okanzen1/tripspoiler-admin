<?php

use App\Mcp\Servers\TripSpoilerServer;
use Laravel\Mcp\Facades\Mcp;

// Claude Desktop bu bilgisayarda "php artisan mcp:start tripspoiler" ile bağlanır (ayar: claude_desktop_config.json)
Mcp::local('tripspoiler', TripSpoilerServer::class);
