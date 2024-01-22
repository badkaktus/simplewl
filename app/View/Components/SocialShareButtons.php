<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Route;

class SocialShareButtons extends Component
{
    private const ROUTES_WITH_SHARING = [
        'wishlist.index',
        'my-wishlist',
        'wish.show',
    ];

    private const DEFAULT_TITLE = 'Share wishlist to:';
    private const DEFAULT_SHARE_TEXT = 'Check out this wishlist on %s';

    public string $url;
    public string $title;
    public string $shareText;

    public function __construct() {
        $this->url = urlencode(url()->current());
        $this->title = $this->getTitle();
        $this->shareText = $this->getShareText();
    }

    public function render(): View|Closure|string
    {
        return view('components.social-share-buttons');
    }

    public function shouldRender(): bool
    {
        return in_array(Route::currentRouteName(), self::ROUTES_WITH_SHARING);
    }

    private function getTitle(): string
    {
        return match (Route::currentRouteName()) {
            'wish.show' => 'Share wish to:',
            default => self::DEFAULT_TITLE,
        };
    }

    private function getShareText(): string
    {
        $host = parse_url(url()->current(), PHP_URL_HOST);

        $shareText = match (Route::currentRouteName()) {
            'wish.show' => 'Check out this wish on %s',
            default => self::DEFAULT_SHARE_TEXT,
        };

        return urlencode(sprintf($shareText, $host));
    }
}
