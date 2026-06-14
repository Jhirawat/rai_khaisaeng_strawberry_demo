<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Member\{CartController,CheckoutController,OrderController as MemberOrderController,ProfileController};
use App\Http\Controllers\Admin\{DashboardController,ProductController,CategoryController,OrderController,PaymentController,InventoryController,UserController,ShippingController,ReportController,ThemeController,CompanySettingsController};

Route::get('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['th','en'], true), 404);
    session(['locale' => $locale]);
    return back();
})->name('language.switch');

Route::get('/api/address/provinces',[AddressController::class,'provinces'])->name('api.address.provinces');
Route::get('/api/address/provinces/{province}/districts',[AddressController::class,'districts'])->name('api.address.districts');
Route::get('/api/address/districts/{district}/subdistricts',[AddressController::class,'subdistricts'])->name('api.address.subdistricts');

Route::get('/',[ShopController::class,'home'])->name('shop.home');
Route::get('/products',[ShopController::class,'products'])->name('shop.products');
Route::get('/products/{product}',[ShopController::class,'show'])->name('shop.product');

Route::get('/login',[AuthController::class,'loginForm'])->name('login');
Route::post('/login',[AuthController::class,'login']);
Route::get('/register',[AuthController::class,'registerForm'])->name('register');
Route::post('/register',[AuthController::class,'register']);
Route::post('/logout',[AuthController::class,'logout'])->name('logout');
Route::get('/auth/{provider}/redirect',[AuthController::class,'redirectToProvider'])->name('social.redirect');
Route::get('/auth/{provider}/callback',[AuthController::class,'handleProviderCallback'])->name('social.callback');

Route::middleware(['auth'])->get('/receipts/{order}',[ReceiptController::class,'show'])->name('receipts.show');
Route::middleware(['auth'])->get('/receipts/{order}/download',[ReceiptController::class,'download'])->name('receipts.download');

Route::middleware(['auth'])->prefix('member')->name('member.')->group(function(){
    Route::get('/profile',[ProfileController::class,'show'])->name('profile');
    Route::patch('/profile',[ProfileController::class,'update'])->name('profile.update');
    Route::post('/addresses',[ProfileController::class,'storeAddress'])->name('addresses.store');
    Route::patch('/addresses/{address}',[ProfileController::class,'updateAddress'])->name('addresses.update');
    Route::delete('/addresses/{address}',[ProfileController::class,'destroyAddress'])->name('addresses.destroy');
    Route::get('/cart',[CartController::class,'index'])->name('cart');
    Route::post('/cart/add/{product}',[CartController::class,'add'])->name('cart.add');
    Route::patch('/cart/item/{item}',[CartController::class,'update'])->name('cart.update');
    Route::delete('/cart/item/{item}',[CartController::class,'destroy'])->name('cart.destroy');
    Route::get('/checkout',[CheckoutController::class,'form'])->name('checkout');
    Route::post('/checkout',[CheckoutController::class,'store'])->name('checkout.store');
    Route::get('/orders',[MemberOrderController::class,'index'])->name('orders');
    Route::get('/orders/{order}',[MemberOrderController::class,'show'])->name('orders.show');
});

// หลังบ้าน: staff/admin/super_admin เข้าฟังก์ชันปฏิบัติงานประจำวันได้
Route::middleware(['auth','role:staff,admin,super_admin'])->prefix('admin')->name('admin.')->group(function(){
    Route::get('/',[DashboardController::class,'index'])->name('dashboard');

    Route::post('products/bulk',[ProductController::class,'bulk'])->name('products.bulk');
    Route::patch('products/{product}/quick-update',[ProductController::class,'quickUpdate'])->name('products.quickUpdate');
    Route::delete('product-images/{image}',[ProductController::class,'destroyImage'])->name('productImages.destroy');
    Route::patch('product-images/{image}/primary',[ProductController::class,'setPrimaryImage'])->name('productImages.primary');
    Route::resource('products',ProductController::class);
    Route::resource('categories',CategoryController::class)->except(['create','edit','show']);

    Route::get('orders',[OrderController::class,'index'])->name('orders.index');
    Route::patch('orders/bulk-update',[OrderController::class,'bulkUpdate'])->name('orders.bulkUpdate');
    Route::get('orders/{order}',[OrderController::class,'show'])->name('orders.show');
    Route::patch('orders/{order}',[OrderController::class,'update'])->name('orders.update');

    Route::get('payments',[PaymentController::class,'index'])->name('payments.index');
    Route::post('payments/{payment}/approve',[PaymentController::class,'approve'])->name('payments.approve');
    Route::post('payments/{payment}/reject',[PaymentController::class,'reject'])->name('payments.reject');

    Route::get('inventory',[InventoryController::class,'index'])->name('inventory.index');
    Route::patch('inventory/bulk-update',[InventoryController::class,'bulkUpdate'])->name('inventory.bulkUpdate');
    Route::patch('inventory/{inventory}',[InventoryController::class,'update'])->name('inventory.update');
    Route::patch('shipping/{shipment}',[ShippingController::class,'update'])->name('shipping.update');
    Route::get('reports',[ReportController::class,'index'])->name('reports.index');
});

// Super Admin เท่านั้น: ตั้งค่าระบบ, ธีม, ข้อมูลร้านค้า, ผู้ใช้งาน/สิทธิ์
Route::middleware(['auth','role:super_admin'])->prefix('admin')->name('admin.')->group(function(){
    Route::get('company-settings',[CompanySettingsController::class,'edit'])->name('company-settings.edit');
    Route::patch('company-settings',[CompanySettingsController::class,'update'])->name('company-settings.update');
    Route::get('theme',[ThemeController::class,'edit'])->name('theme.edit');
    Route::patch('theme',[ThemeController::class,'update'])->name('theme.update');
    Route::post('theme/preset/{preset}',[ThemeController::class,'preset'])->name('theme.preset');
    Route::post('theme/reset',[ThemeController::class,'reset'])->name('theme.reset');

    Route::patch('users/{user}/toggle-status',[UserController::class,'toggleStatus'])->name('users.toggleStatus');
    Route::post('users/{user}/reset-password',[UserController::class,'resetPassword'])->name('users.resetPassword');
    Route::resource('users',UserController::class);
});
