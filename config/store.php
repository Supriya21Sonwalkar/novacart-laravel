<?php
return ['modules'=>[
 'products'=>['title'=>'Products','fields'=>['name','sku','category_id','brand_id','price','old_price','stock','delivery_type','delivery_minutes','image','gallery','description','specifications','variants','tags','badge','featured','active']],
 'orders'=>['title'=>'Orders','readonly'=>true],
 'inventory'=>['title'=>'Inventory','fields'=>['product_id','adjustment','reason']],
 'categories'=>['title'=>'Categories','fields'=>['name','parent_id','image','description','position','active']],
 'brands'=>['title'=>'Brands','fields'=>['name','logo','active']],
 'users'=>['title'=>'Users','fields'=>['name','email','password','password_confirmation','role_id','active']],
 'roles'=>['title'=>'Roles & permissions','fields'=>['name','permissions']],
 'coupons'=>['title'=>'Coupons','fields'=>['name','type','value','minimum','maximum','starts','expires','limit','email','active']],
 'shipping_methods'=>['title'=>'Shipping','fields'=>['name','charge','zones','estimated_days','free_threshold','active']],
 'payments'=>['title'=>'Payments','readonly'=>true],
 'shipments'=>['title'=>'Shipments','fields'=>['tracking_number']],
 'reviews'=>['title'=>'Reviews','fields'=>['approved','response']],
 'reports'=>['title'=>'Reports & analytics','readonly'=>true],
 'banners'=>['title'=>'Banners & content','fields'=>['name','image','url','description','active']],
 'settings'=>['title'=>'Settings','fields'=>['name','logo','support_email','tax','free_threshold','payment_methods','about','terms','privacy','returns','seo_description','social_links']],
 'support_messages'=>['title'=>'Support messages','readonly'=>true],
 'activity_logs'=>['title'=>'Activity log','readonly'=>true]
]];
