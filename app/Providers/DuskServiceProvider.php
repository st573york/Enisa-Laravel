<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\Browser;

class DuskServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Browser::macro('loginAsRole', function ($role, $dusk = true, $auth = true) {
            $user = User::with('permissions')->whereHas('permissions', function ($query) use ($role) {
                $query
                    ->when($role == 'admin', function ($query) {
                        $query->where('role_id', 1);
                    })
                    ->when($role == 'ppoc', function ($query) {
                        $query->where('role_id', 5);
                    })
                    ->when($role == 'poc', function ($query) {
                        $query->where('role_id', 2);
                    })
                    ->when($role == 'operator', function ($query) {
                        $query->where('role_id', 3);
                    })
                    ->when($role == 'viewer', function ($query) {
                        $query->where('role_id', 4);
                    });
            })->first();
            
            // Browser login
            if ($dusk) {
                $this->loginAs($user);
            }
            
            // Auth login
            if ($auth)
            {
                $dbUser = User::where('email', $user->email)->first();
                Auth::loginUsingId($dbUser->id);
            }

            $this->user = $user;

            return $this;
        });

        Browser::macro('scrollToTopOrBottom', function ($scrollTo) {
            if ($scrollTo == 'top') {
                $this->script('scrollToTop();');
            }
            elseif ($scrollTo == 'bottom') {
                $this->script('scrollToBottom();');
            }
            $this->pause(1000);

            return $this;
        });

        Browser::macro('enableCursor', function () {
            $this->script(<<<EOF
                var seleniumFollowerImg=document.createElement("img");
                seleniumFollowerImg.setAttribute('src', 'data:image/png;base64,'
                    + 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAeCAQAAACGG/bgAAAAAmJLR0QA/4ePzL8AAAAJcEhZcwAA'
                    + 'HsYAAB7GAZEt8iwAAAAHdElNRQfgAwgMIwdxU/i7AAABZklEQVQ4y43TsU4UURSH8W+XmYwkS2I0'
                    + '9CRKpKGhsvIJjG9giQmliHFZlkUIGnEF7KTiCagpsYHWhoTQaiUUxLixYZb5KAAZZhbunu7O/PKf'
                    + 'e+fcA+/pqwb4DuximEqXhT4iI8dMpBWEsWsuGYdpZFttiLSSgTvhZ1W/SvfO1CvYdV1kPghV68a3'
                    + '0zzUWZH5pBqEui7dnqlFmLoq0gxC1XfGZdoLal2kea8ahLoqKXNAJQBT2yJzwUTVt0bS6ANqy1ga'
                    + 'VCEq/oVTtjji4hQVhhnlYBH4WIJV9vlkXLm+10R8oJb79Jl1j9UdazJRGpkrmNkSF9SOz2T71s7M'
                    + 'SIfD2lmmfjGSRz3hK8l4w1P+bah/HJLN0sys2JSMZQB+jKo6KSc8vLlLn5ikzF4268Wg2+pPOWW6'
                    + 'ONcpr3PrXy9VfS473M/D7H+TLmrqsXtOGctvxvMv2oVNP+Av0uHbzbxyJaywyUjx8TlnPY2YxqkD'
                    + 'dAAAAABJRU5ErkJggg==');
                seleniumFollowerImg.setAttribute('id', 'selenium_mouse_follower');
                seleniumFollowerImg.setAttribute('style', 'position: absolute; z-index: 99999999999; pointer-events: none;');
                document.body.appendChild(seleniumFollowerImg);
                $(document).mousemove(function(e) {
                    $("#selenium_mouse_follower").animate({left:e.pageX, top:e.pageY}, 'fast');
                });
            EOF);
        });
    }
}
