<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\BargainingOfferController;
use App\Http\Controllers\Api\ClientProfileController;
use App\Http\Controllers\Api\Client\ClientMessageController;
use App\Http\Controllers\Api\Client\ClientPaymentMethodController;
use App\Http\Controllers\Api\Client\ClientWorkspaceController;
use App\Http\Controllers\Api\Client\SupportTicketController as ClientSupportTicketController;
use App\Http\Controllers\Api\Owner\SupportTicketController as OwnerSupportTicketController;
use App\Http\Controllers\Api\DesignCustomizationController;
use App\Http\Controllers\Api\DesignProofController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DesignPostController;
use App\Http\Controllers\Api\FulfillmentController;
use App\Http\Controllers\Api\JobPostApplicationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderAssignmentController;
use App\Http\Controllers\Api\OrderExceptionController;
use App\Http\Controllers\Api\OrderQuoteController;
use App\Http\Controllers\Api\OrderRevisionController;
use App\Http\Controllers\Api\OrderStageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\ShopMemberController;
use App\Http\Controllers\Api\ShopProjectController;
use App\Http\Controllers\Api\SmartOpsController;
use App\Http\Controllers\Api\ShopServiceController;
use App\Http\Controllers\Api\Owner\DisputeCaseController;
use App\Http\Controllers\Api\Owner\OwnerActionController;
use App\Http\Controllers\Api\Owner\CourierController;
use App\Http\Controllers\Api\Owner\MessageThreadController;
use App\Http\Controllers\Api\Owner\OwnerPricingController;
use App\Http\Controllers\Api\Owner\OwnerSettingsController;
use App\Http\Controllers\Api\Owner\OwnerWorkspaceController;
use App\Http\Controllers\Api\Owner\QualityCheckController;
use App\Http\Controllers\Api\Owner\RawMaterialController;
use App\Http\Controllers\Api\Owner\SupplierController;
use App\Http\Controllers\Api\Owner\SupplyOrderController;
use App\Http\Controllers\Api\Owner\WorkforceScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/analytics/shops/{shop}/metrics', [AnalyticsController::class, 'shopMetrics']);

    Route::prefix('client')->middleware('role:client')->group(function () {
        Route::get('/workspace', [ClientWorkspaceController::class, 'index']);
        Route::get('/payment-methods', [ClientPaymentMethodController::class, 'index']);
        Route::post('/payment-methods', [ClientPaymentMethodController::class, 'store']);
        Route::put('/payment-methods/{paymentMethod}', [ClientPaymentMethodController::class, 'update']);
        Route::delete('/payment-methods/{paymentMethod}', [ClientPaymentMethodController::class, 'destroy']);

        Route::get('/threads', [ClientMessageController::class, 'index']);
        Route::post('/threads', [ClientMessageController::class, 'store']);
        Route::post('/threads/{thread}/messages', [ClientMessageController::class, 'postMessage']);

        Route::get('/support-tickets', [ClientSupportTicketController::class, 'index']);
        Route::post('/support-tickets', [ClientSupportTicketController::class, 'store']);
        Route::put('/support-tickets/{supportTicket}', [ClientSupportTicketController::class, 'update']);
    });

    Route::post('/analytics/shops/{shop}/refresh', [AnalyticsController::class, 'refreshShopMetrics']);
    Route::get('/analytics/orders/{order}/risk', [AnalyticsController::class, 'orderRisk']);
    Route::get('/analytics/recommendations', [AnalyticsController::class, 'recommendations']);
    Route::get('/smart-ops/shops/{shop}/summary', [SmartOpsController::class, 'summary']);
    Route::post('/smart-ops/shops/{shop}/scan', [SmartOpsController::class, 'scan']);
    Route::post('/smart-ops/alerts/{alert}/resolve', [SmartOpsController::class, 'resolve']);

    Route::get('/client-profile', [ClientProfileController::class, 'show']);
    Route::get('/client-profile/options', [ClientProfileController::class, 'options']);
    Route::put('/client-profile', [ClientProfileController::class, 'update']);
    Route::post('/client-profile/addresses', [ClientProfileController::class, 'storeAddress']);
    Route::put('/client-profile/addresses/{address}', [ClientProfileController::class, 'updateAddress']);
    Route::delete('/client-profile/addresses/{address}', [ClientProfileController::class, 'deleteAddress']);

    Route::get('/design-customizations', [DesignCustomizationController::class, 'index']);
    Route::post('/design-customizations', [DesignCustomizationController::class, 'store']);
    Route::get('/design-customizations/{designCustomization}', [DesignCustomizationController::class, 'show']);
    Route::put('/design-customizations/{designCustomization}', [DesignCustomizationController::class, 'update']);
    Route::post('/design-customizations/suggest-price', [DesignCustomizationController::class, 'suggestPrice']);
    Route::get('/design-customizations/{designCustomization}/proofs', [DesignProofController::class, 'index']);
    Route::post('/design-customizations/{designCustomization}/proofs', [DesignProofController::class, 'store']);
    Route::post('/design-customizations/{designCustomization}/proofs/{designProof}/respond', [DesignProofController::class, 'respond']);

    Route::get('/shop-projects', [ShopProjectController::class, 'index']);
    Route::post('/shop-projects', [ShopProjectController::class, 'store']);
    Route::put('/shop-projects/{shopProject}', [ShopProjectController::class, 'update']);
    Route::post('/shop-projects/{shopProject}/order', [ShopProjectController::class, 'order']);

    Route::get('/design-posts/{designPost}/bargaining-offers', [BargainingOfferController::class, 'index']);
    Route::post('/design-posts/{designPost}/bargaining-offers', [BargainingOfferController::class, 'store']);
    Route::post('/bargaining-offers/{bargainingOffer}/respond', [BargainingOfferController::class, 'respond']);

    Route::get('/shops', [ShopController::class, 'index']);
    Route::post('/shops', [ShopController::class, 'store']);
    Route::get('/shops/{shop}', [ShopController::class, 'show']);
    Route::put('/shops/{shop}', [ShopController::class, 'update']);
    Route::post('/shops/{shop}/approve', [ShopController::class, 'approve'])->middleware('role:admin');
    Route::post('/shops/{shop}/reject', [ShopController::class, 'reject'])->middleware('role:admin');

    Route::get('/shop-members', [ShopMemberController::class, 'index']);
    Route::post('/shop-members', [ShopMemberController::class, 'store']);
    Route::put('/shop-members/{shopMember}', [ShopMemberController::class, 'update']);

    Route::get('/shop-services', [ShopServiceController::class, 'index']);
    Route::post('/shop-services', [ShopServiceController::class, 'store']);
    Route::put('/shop-services/{shopService}', [ShopServiceController::class, 'update']);
    Route::delete('/shop-services/{shopService}', [ShopServiceController::class, 'destroy']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    Route::get('/orders/{order}/assignments', [OrderAssignmentController::class, 'index']);
    Route::post('/orders/{order}/assignments', [OrderAssignmentController::class, 'store']);
    Route::put('/assignments/{assignment}', [OrderAssignmentController::class, 'update']);
    Route::post('/assignments/{assignment}/accept', [OrderAssignmentController::class, 'accept']);
    Route::post('/assignments/{assignment}/complete', [OrderAssignmentController::class, 'complete']);

    Route::get('/fulfillments', [FulfillmentController::class, 'index']);
    Route::get('/orders/{order}/fulfillment', [FulfillmentController::class, 'show']);
    Route::post('/orders/{order}/fulfillment', [FulfillmentController::class, 'store']);
    Route::put('/orders/{order}/fulfillment', [FulfillmentController::class, 'update']);
    Route::post('/orders/{order}/fulfillment/status', [FulfillmentController::class, 'updateStatus']);
    Route::post('/orders/{order}/fulfillment/ready', [FulfillmentController::class, 'markReady']);
    Route::post('/orders/{order}/fulfillment/shipped', [FulfillmentController::class, 'markShipped']);
    Route::post('/orders/{order}/fulfillment/delivered', [FulfillmentController::class, 'markDelivered']);
    Route::post('/orders/{order}/fulfillment/picked-up', [FulfillmentController::class, 'markPickedUp']);
    Route::get('/orders/{order}/revisions', [OrderRevisionController::class, 'index']);
    Route::post('/orders/{order}/revisions', [OrderRevisionController::class, 'store']);
    Route::get('/orders/{order}/revisions/{revision}', [OrderRevisionController::class, 'show']);
    Route::put('/orders/{order}/revisions/{revision}', [OrderRevisionController::class, 'update']);
    Route::post('/orders/{order}/revisions/{revision}/claim', [OrderRevisionController::class, 'claim']);
    Route::post('/orders/{order}/revisions/{revision}/upload-preview', [OrderRevisionController::class, 'uploadPreview']);
    Route::post('/orders/{order}/revisions/{revision}/approve', [OrderRevisionController::class, 'approve']);
    Route::post('/orders/{order}/revisions/{revision}/reject', [OrderRevisionController::class, 'reject']);
    Route::post('/orders/{order}/revisions/{revision}/implement', [OrderRevisionController::class, 'implement']);
    Route::post('/orders/{order}/revisions/{revision}/cancel', [OrderRevisionController::class, 'cancel']);

    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirm']);
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject']);

    Route::get('/design-posts', [DesignPostController::class, 'index']);
    Route::post('/design-posts', [DesignPostController::class, 'store']);
    Route::get('/design-posts/{designPost}', [DesignPostController::class, 'show']);
    Route::put('/design-posts/{designPost}', [DesignPostController::class, 'update']);
    Route::post('/design-posts/{designPost}/select-shop', [DesignPostController::class, 'selectShop']);
    Route::get('/design-posts/{designPost}/applications', [JobPostApplicationController::class, 'index']);
    Route::post('/design-posts/{designPost}/applications', [JobPostApplicationController::class, 'store']);
    Route::put('/job-post-applications/{jobPostApplication}', [JobPostApplicationController::class, 'update']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::get('/orders/{order}/quotes', [OrderQuoteController::class, 'index']);
    Route::post('/orders/{order}/quotes', [OrderQuoteController::class, 'store']);
    Route::get('/orders/{order}/quotes/{quote}', [OrderQuoteController::class, 'show']);
    Route::put('/orders/{order}/quotes/{quote}', [OrderQuoteController::class, 'update']);
    Route::post('/orders/{order}/quotes/{quote}/accept', [OrderQuoteController::class, 'accept']);
    Route::post('/orders/{order}/quotes/{quote}/reject', [OrderQuoteController::class, 'reject']);

    Route::get('/orders/{order}/stages', [OrderStageController::class, 'index']);
    Route::post('/orders/{order}/stages', [OrderStageController::class, 'store']);
    Route::put('/orders/{order}/stages/{stage}', [OrderStageController::class, 'update']);
    Route::post('/orders/{order}/stages/{stage}/complete', [OrderStageController::class, 'complete']);
    Route::post('/orders/{order}/stages/{stage}/fail', [OrderStageController::class, 'fail']);

    Route::get('/orders/{order}/exceptions', [OrderExceptionController::class, 'index']);
    Route::post('/orders/{order}/exceptions', [OrderExceptionController::class, 'store']);
    Route::put('/exceptions/{exception}', [OrderExceptionController::class, 'update']);
    Route::post('/exceptions/{exception}/resolve', [OrderExceptionController::class, 'resolve']);

    Route::prefix('owner')->middleware('role:owner')->group(function () {
        Route::get('/workspace', [OwnerWorkspaceController::class, 'index']);
        Route::get('/settings', [OwnerSettingsController::class, 'show']);
        Route::put('/settings', [OwnerSettingsController::class, 'update']);
        Route::get('/pricing', [OwnerPricingController::class, 'show']);
        Route::put('/pricing', [OwnerPricingController::class, 'update']);

        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);

        Route::get('/couriers', [CourierController::class, 'index']);
        Route::post('/couriers', [CourierController::class, 'store']);

        Route::get('/raw-materials', [RawMaterialController::class, 'index']);
        Route::post('/raw-materials', [RawMaterialController::class, 'store']);
        Route::put('/raw-materials/{rawMaterial}', [RawMaterialController::class, 'update']);

        Route::get('/supply-orders', [SupplyOrderController::class, 'index']);
        Route::post('/supply-orders', [SupplyOrderController::class, 'store']);
        Route::put('/supply-orders/{supplyOrder}', [SupplyOrderController::class, 'update']);

        Route::get('/quality-checks', [QualityCheckController::class, 'index']);
        Route::post('/quality-checks', [QualityCheckController::class, 'store']);
        Route::put('/quality-checks/{qualityCheck}', [QualityCheckController::class, 'update']);

        Route::get('/workforce-schedules', [WorkforceScheduleController::class, 'index']);
        Route::post('/workforce-schedules', [WorkforceScheduleController::class, 'store']);
        Route::put('/workforce-schedules/{workforceSchedule}', [WorkforceScheduleController::class, 'update']);

        Route::get('/disputes', [DisputeCaseController::class, 'index']);
        Route::post('/disputes', [DisputeCaseController::class, 'store']);
        Route::put('/disputes/{disputeCase}', [DisputeCaseController::class, 'update']);

        Route::get('/threads', [MessageThreadController::class, 'index']);
        Route::post('/threads', [MessageThreadController::class, 'store']);
        Route::post('/threads/{thread}/messages', [MessageThreadController::class, 'postMessage']);

        Route::post('/actions/orders/{order}/reassign-staff', [OwnerActionController::class, 'reassignStaff']);
        Route::post('/actions/orders/{order}/approve-production-plan', [OwnerActionController::class, 'approveProductionPlan']);
        Route::post('/actions/restock-requests', [OwnerActionController::class, 'createRestockRequest']);
        Route::post('/actions/orders/{order}/follow-up-payment', [OwnerActionController::class, 'followUpPayment']);
        Route::post('/actions/orders/{order}/escalate', [OwnerActionController::class, 'escalateOrder']);
        Route::post('/actions/orders/{order}/pause', [OwnerActionController::class, 'pauseProduction']);
        Route::post('/actions/orders/{order}/resume', [OwnerActionController::class, 'resumeProduction']);
        Route::post('/actions/alerts/{alert}/resolve', [OwnerActionController::class, 'resolveAlert']);
        Route::post('/actions/alerts/{alert}/snooze', [OwnerActionController::class, 'snoozeAlert']);
        Route::post('/actions/orders/{order}/quality-checks', [OwnerActionController::class, 'createQualityCheck']);
        Route::post('/actions/orders/{order}/package-ready', [OwnerActionController::class, 'markPackageReady']);
        Route::post('/actions/orders/{order}/assign-courier', [OwnerActionController::class, 'assignCourier']);
        Route::post('/actions/notifications/maintain', [OwnerActionController::class, 'maintainNotifications']);
    });
});
