<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Proyek ini adalah REST API. Root diarahkan ke dokumentasi interaktif
| supaya penguji yang membuka domain aplikasi langsung melihat /docs/api.
*/

Route::redirect('/', '/docs/api');
