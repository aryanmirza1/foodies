<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\MenuItem;
use App\Models\RestaurantSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            RestaurantSetting::query()->updateOrCreate(
                ['id' => 1],
                [
                    'restaurant_name' => 'Foodies Kitchen',
                    'app_name' => 'Foodies',
                    'tagline' => 'Fresh food, fast taps',
                    'phone' => '+92 300 0000000',
                    'email' => 'hello@foodies.test',
                    'whatsapp' => '+92 300 0000000',
                    'address' => 'Main Road, Sargodha',
                    'about_text' => 'A single-restaurant ordering app for fresh meals, pickup, and delivery.',
                    'primary_color' => '#FFC107',
                    'secondary_color' => '#111111',
                    'background_color' => '#F8F2E9',
                    'button_color' => '#FFC107',
                    'is_open' => true,
                ]
            );

            HomeSection::query()->updateOrCreate(
                ['id' => 1],
                [
                    'hero_title' => 'What would you like to eat today?',
                    'hero_subtitle' => 'Browse the menu, add favorites, and keep your cart ready.',
                    'search_placeholder' => 'Search biryani, pizza, drinks...',
                    'popular_section_title' => 'Popular Items',
                    'category_section_title' => 'Categories',
                    'announcement_text' => 'Fresh deals are available today.',
                ]
            );

            $banners = [
                ['title' => 'Fresh meals made today', 'subtitle' => 'Hot favorites ready for pickup or delivery.', 'sort_order' => 1],
                ['title' => 'Family dinner picks', 'subtitle' => 'Shareable food for every table.', 'sort_order' => 2],
                ['title' => 'Cold drinks with every craving', 'subtitle' => 'Pair your meal with something refreshing.', 'sort_order' => 3],
            ];

            foreach ($banners as $banner) {
                Banner::query()->updateOrCreate(['title' => $banner['title']], $banner + ['is_active' => true]);
            }

            $categories = collect([
                ['name' => 'Burgers', 'slug' => 'burgers', 'icon' => 'B', 'sort_order' => 1],
                ['name' => 'Pizza', 'slug' => 'pizza', 'icon' => 'P', 'sort_order' => 2],
                ['name' => 'Biryani', 'slug' => 'biryani', 'icon' => 'B', 'sort_order' => 3],
                ['name' => 'Drinks', 'slug' => 'drinks', 'icon' => 'D', 'sort_order' => 4],
                ['name' => 'Desserts', 'slug' => 'desserts', 'icon' => 'S', 'sort_order' => 5],
            ])->mapWithKeys(function (array $category): array {
                $model = Category::query()->updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]);

                return [$category['slug'] => $model];
            });

            $items = [
                ['category' => 'burgers', 'name' => 'Classic Beef Burger', 'slug' => 'classic-beef-burger', 'description' => 'Juicy beef patty with cheese, lettuce, and house sauce.', 'price' => 7.50, 'is_popular' => true, 'preparation_time' => 18, 'sort_order' => 1],
                ['category' => 'burgers', 'name' => 'Crispy Chicken Burger', 'slug' => 'crispy-chicken-burger', 'description' => 'Crunchy chicken fillet with mayo and pickles.', 'price' => 6.75, 'is_popular' => true, 'preparation_time' => 16, 'sort_order' => 2],
                ['category' => 'pizza', 'name' => 'Margherita Pizza', 'slug' => 'margherita-pizza', 'description' => 'Mozzarella, tomato sauce, basil, and olive oil.', 'price' => 9.00, 'is_popular' => false, 'preparation_time' => 20, 'sort_order' => 3],
                ['category' => 'pizza', 'name' => 'Chicken Fajita Pizza', 'slug' => 'chicken-fajita-pizza', 'description' => 'Spiced chicken, peppers, onions, and melted cheese.', 'price' => 11.25, 'discount_price' => 9.99, 'is_popular' => true, 'preparation_time' => 24, 'sort_order' => 4],
                ['category' => 'biryani', 'name' => 'Chicken Biryani', 'slug' => 'chicken-biryani', 'description' => 'Fragrant rice layered with tender chicken and spices.', 'price' => 5.50, 'is_popular' => true, 'preparation_time' => 12, 'sort_order' => 5],
                ['category' => 'biryani', 'name' => 'Beef Biryani', 'slug' => 'beef-biryani', 'description' => 'Slow-cooked beef with masala rice and raita.', 'price' => 6.25, 'is_popular' => false, 'preparation_time' => 14, 'sort_order' => 6],
                ['category' => 'drinks', 'name' => 'Mint Margarita', 'slug' => 'mint-margarita', 'description' => 'Cold lemon mint drink with crushed ice.', 'price' => 2.25, 'is_popular' => true, 'preparation_time' => 5, 'sort_order' => 7],
                ['category' => 'drinks', 'name' => 'Fresh Lime Soda', 'slug' => 'fresh-lime-soda', 'description' => 'Sparkling lime drink with a clean citrus finish.', 'price' => 1.95, 'is_popular' => false, 'preparation_time' => 5, 'sort_order' => 8],
                ['category' => 'desserts', 'name' => 'Kheer Cup', 'slug' => 'kheer-cup', 'description' => 'Creamy rice pudding topped with nuts.', 'price' => 2.75, 'is_popular' => false, 'preparation_time' => 4, 'sort_order' => 9],
                ['category' => 'desserts', 'name' => 'Chocolate Brownie', 'slug' => 'chocolate-brownie', 'description' => 'Warm brownie with a soft chocolate center.', 'price' => 3.25, 'is_popular' => true, 'preparation_time' => 8, 'sort_order' => 10],
            ];

            foreach ($items as $item) {
                $category = $categories[$item['category']];
                unset($item['category']);

                MenuItem::query()->updateOrCreate(
                    ['slug' => $item['slug']],
                    $item + [
                        'category_id' => $category->id,
                        'is_available' => true,
                    ]
                );
            }
        });
    }
}
