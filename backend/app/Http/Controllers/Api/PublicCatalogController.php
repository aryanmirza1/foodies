<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\HomeSectionResource;
use App\Http\Resources\MenuItemResource;
use App\Http\Resources\RestaurantSettingsResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\MenuItem;
use App\Models\RestaurantSetting;
use Illuminate\Database\Eloquent\Builder;

class PublicCatalogController extends Controller
{
    public function settings(): RestaurantSettingsResource
    {
        return new RestaurantSettingsResource(RestaurantSetting::query()->firstOrFail());
    }

    public function home(): HomeSectionResource
    {
        return new HomeSectionResource(HomeSection::query()->firstOrFail());
    }

    public function banners()
    {
        $now = now();

        $banners = Banner::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('start_date')->orWhere('start_date', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $now))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return BannerResource::collection($banners);
    }

    public function categories()
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['menuItems' => fn (Builder $query) => $query->where('is_available', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function menuItems()
    {
        $items = MenuItem::query()
            ->with('category')
            ->where('is_available', true)
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->orderByDesc('is_popular')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return MenuItemResource::collection($items);
    }

    public function menuItem(string $slug): MenuItemResource
    {
        $item = MenuItem::query()
            ->with('category')
            ->where('slug', $slug)
            ->where('is_available', true)
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->firstOrFail();

        return new MenuItemResource($item);
    }
}
