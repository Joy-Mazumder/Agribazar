<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['consumer_id'] ?? $_SESSION['seller_id'] ?? null;
$is_seller = isset($_SESSION['seller_id']);

if (!$user_id) {
    header('Location: login.html');
    exit;
}

// Get cart items for current user
$cart_sql = "SELECT c.cart_item_id, c.quantity, p.product_id, p.product_name, p.price, p.image_url, p.weight, p.quantity_available
             FROM cart_items c 
             JOIN products p ON c.product_id = p.product_id 
             WHERE c.user_id = ? 
             ORDER BY c.cart_item_id DESC";
$stmt = $conn->prepare($cart_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

// Calculate totals
$cart_total = 0;
$cart_count = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - AgriBazar</title>
    <link rel="Icon" href="assets/img/logo.png">
    <link rel="stylesheet" href="assets/plugins/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Payment Modal Styles */
        .payment-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .payment-modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 30px;
            border: none;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .payment-form {
            text-align: center;
        }
        
        .payment-form h3 {
            color: #2e7d32;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .payment-form input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .payment-form input:focus {
            outline: none;
            border-color: #4caf50;
        }
        
        .payment-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pay-btn, .cancel-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .pay-btn {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
        }
        
        .pay-btn:hover {
            background: linear-gradient(135deg, #1b5e20 0%, #388e3c 100%);
        }
        
        .cancel-btn {
            background: #f44336;
            color: white;
        }
        
        .cancel-btn:hover {
            background: #d32f2f;
        }
        
        /* Success Modal */
        .success-modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .success-modal-content {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            margin: 15% auto;
            padding: 40px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .success-modal h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .success-modal p {
            font-size: 18px;
            line-height: 1.5;
            margin-bottom: 30px;
        }
        
        .continue-btn {
            background: white;
            color: #2e7d32;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .continue-btn:hover {
            background: #f5f5f5;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4caf50;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            z-index: 1002;
        }
        
        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .notification.error {
            background: #f44336;
        }
    </style>
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="spinner">
            <div class="ring ring1"></div>
            <div class="ring ring2"></div>
            <div class="ring ring3"></div>
        </div>
    </div>

    <!-- Header -->
    <section class="header custom-header">
        <div class="container">
            <div class="row align-center">
                <div class="col-xxl-2">
                    <div class="logo">
                        <a href="index.php">
                            <img src="assets/img/logo.png" alt="img">
                        </a>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <ul class="menu custom-menu d-flex align-center text-capitalize flex-wrap justify-center">
                        <li><a href="index.php">home</a></li>
                        <li><a href="#">about</a></li>
                        <li><a href="shop.php">shop</a></li>
                        <li><a href="#">blog</a></li>
                        <li><a href="#">contact</a></li>
                    </ul>
                </div>
                <div class="col-xxl-4">
                    <div class="header-right d-flex justify-space-between align-center">
                        <div class="wish-list text-center">
                            <a href="#" style="color: #2e7d32;">
                                <i class="fa fa-heart"></i>
                            </a>
                        </div>
                        
                        <img src="assets/img/svg/star-icon-1.svg" alt="svg">
                        
                        <!-- Dashboard Button -->
                        <div class="dashboard-section">
                            <?php if ($is_seller): ?>
                                <a href="seller_dashboard.php" class="dashboard-btn" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 13px;">
                                    <i class="fa fa-tachometer"></i> Dashboard
                                </a>
                            <?php elseif ($user_id): ?>
                                <a href="consumer_dashboard.php" class="dashboard-btn" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 13px;">
                                    <i class="fa fa-user"></i> Dashboard
                                </a>
                            <?php else: ?>
                                <a href="login.html" class="dashboard-btn" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 13px;">
                                    <i class="fa fa-sign-in"></i> Login
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <img src="assets/img/svg/star-icon-1.svg" alt="svg">
                        
                        <div class="user-cart d-flex align-center">
                            <div class="cart-icon">
                                <a href="cart.php" style="color: #2e7d32;">
                                    <i class="fa fa-shopping-cart"></i>
                                </a>
                                <span class="text-center" id="cartCounter" style="background: #ff6b35; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; font-weight: 700;"><?php echo $cart_result->num_rows; ?></span>
                            </div>
                            <a href="cart.php" style="color: #2e7d32; text-decoration: none;">
                                <h6 class="fw-600"><span>cart items</span>৳<span id="cartTotal">0.00</span></h6>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cart Banner -->
    <section class="banner-area" style="background-image: url('assets/img/login-register-page/banner-bg.jpg');">
        <div class="container">
            <div class="row align-center">
                <div class="col-xxl-7">
                    <div class="banner-content">
                        <div class="banner-title">
                            <h1 class="text-capitalize"><span>cart</span> items</h1>
                        </div>
                        <div class="banner-path d-flex align-center">
                            <a href="index.php">home</a>
                            <span>-</span>
                            <a href="cart.php">cart</a>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-5">
                    <div class="banner-img">
                        <img src="assets/img/banner/farmer-cow.png" alt="img">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cart Items Section -->
    <section class="cart-item-area pb-80 pt-80">
        <div class="container">
            <div class="row">
                <div class="col-xxl-12">
                    <div class="cart-item">
                        <?php if ($cart_result->num_rows > 0): ?>
                            <table class="cart">
                                <thead class="cart-header">
                                    <tr>
                                        <th class="cart-product-title">Image</th>
                                        <th class="cart-product-name">Product</th>
                                        <th class="cart-product-price">Price</th>
                                        <th class="cart-product-quantity">Quantity</th>
                                        <th class="cart-product-subtotal">Total</th>
                                        <th class="cart-product-remove">Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $cart_result->fetch_assoc()): 
                                        $subtotal = $item['price'] * $item['quantity'];
                                        $cart_total += $subtotal;
                                        $cart_count++;
                                    ?>
                                        <tr data-cart-id="<?php echo $item['cart_item_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
                                            <td class="ms-product-thumbnail">
                                                <a href="#">
                                                    <?php if (!empty($item['image_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                             onerror="this.src='assets/img/default-product.jpg'">
                                                    <?php else: ?>
                                                        <img src="assets/img/default-product.jpg" alt="Product Image">
                                                    <?php endif; ?>
                                                </a>
                                            </td>
                                            <td class="ms-product-name">
                                                <div class="ms-product-info">
                                                    <h4><a href="#"><?php echo htmlspecialchars($item['product_name']); ?></a></h4>
                                                    <span><?php echo htmlspecialchars($item['weight'] ?? 'N/A'); ?></span>
                                                </div>
                                            </td>
                                            <td class="ms-product-price">
                                                <span class="amount">৳<?php echo number_format($item['price'], 2); ?></span>
                                            </td>
                                            <td class="ms-product-quantity">
                                                <div class="ms-quntity-box">
                                                    <div class="ms-quantity-box-details">
                                                        <div class="ms-quantity-minus" onclick="updateQuantity(<?php echo $item['cart_item_id']; ?>, 'decrease')">-</div>
                                                        <input type="text" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['quantity_available']; ?>" class="quantity-input" readonly>
                                                        <div class="ms-quantity-plus" onclick="updateQuantity(<?php echo $item['cart_item_id']; ?>, 'increase')">+</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="ms-product-subtotal">
                                                <span class="subtotal-amount">৳<?php echo number_format($subtotal, 2); ?></span>
                                            </td>
                                            <td class="ms-product-remove">
                                                <button type="button" onclick="removeItem(<?php echo $item['cart_item_id']; ?>)">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            
                            <div class="row align-center justify-space-between pt-20">
                                <!-- Cart Total Display (Left) -->
                                <div class="col-md-6">
                                    <div class="cart-total-display">
                                        <h4 style="color: #2e7d32; font-size: 24px;">Total: ৳<span id="grandTotal"><?php echo number_format($cart_total, 2); ?></span></h4>
                                    </div>
                                </div>
                            
                                <!-- Buy Now & Empty Cart (Right) -->
                                <div class="col-md-6">
                                    <div class="update-empty-cart text-right">
                                        <button class="btn" onclick="showPaymentModal()" style="background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%); color: white; padding: 12px 25px; margin-right: 10px;">Buy Now</button>
                                        <button class="btn btn-empty" onclick="emptyCart()" style="background: #f44336; color: white; padding: 12px 25px;">Empty Cart</button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-cart" style="text-align: center; padding: 60px 20px;">
                                <i class="fa fa-shopping-cart" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
                                <h3 style="color: #2e7d32; margin-bottom: 10px;">Your Cart is Empty</h3>
                                <p style="color: #666; margin-bottom: 30px;">Start shopping to add items to your cart</p>
                                <a href="shop.php" class="btn" style="background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%); color: white; padding: 12px 25px; text-decoration: none;">Continue Shopping</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Payment Modal -->
    <div id="paymentModal" class="payment-modal">
        <div class="payment-modal-content">
            <div class="payment-form">
                <h3><i class="fa fa-credit-card"></i> Payment Information</h3>
                <p style="color: #666; margin-bottom: 20px;">Total Amount: ৳<span id="paymentTotal"><?php echo number_format($cart_total, 2); ?></span></p>
                <div style="background: #f0f8ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; color: #2e7d32;">
                    <strong>Payment Field Guide:</strong><br>
                    • <strong>MM/YY</strong> = Month/Year (e.g., 12/25 for December 2025)<br>
                    • <strong>CVC</strong> = 3-digit security code on back of card (e.g., 123)
                </div>
                <form id="paymentForm">
                    <input type="text" id="cardNumber" placeholder="Card Number (any 16 digits like 1234 5678 9012 3456)" maxlength="19" required>
                    <input type="text" id="cardExpiry" placeholder="Expiry Month/Year (MM/YY like 12/25)" maxlength="5" required>
                    <input type="text" id="cardCVC" placeholder="Security Code (3 digits like 123)" maxlength="3" required>
                    <input type="text" id="cardName" placeholder=" Name " required>
                    <input type="text" id="address" placeholder="Your Current Address" required>
                    
                    <div class="payment-buttons">
                        <button type="button" class="cancel-btn" onclick="hidePaymentModal()">Cancel</button>
                        <button type="submit" class="pay-btn">Pay Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="success-modal">
        <div class="success-modal-content">
            <h2><i class="fa fa-check-circle"></i> Payment Successful!</h2>
            <p>Thank you for your purchase! Your order has been placed successfully.</p>
            <p><strong>🚚 "We received your payment and we are in front of your house. Open the door!"</strong></p>
            <button class="continue-btn" onclick="continueShopping()">Continue Shopping</button>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <!-- Feature Area -->
    <section class="feature custom-feature pt-20 pb-20">
        <div class="container">
            <div class="row">
                <div class="col-xxl-3">
                    <div class="single-feature">
                        <div class="feature-img">
                            <img src="assets/img/feature/feature-2.jpg" alt="img">
                        </div>
                        <div class="feature-content">
                            <h5>free shipping</h5>
                            <p>shipping on your order. no extra charges for delivery.</p>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3">
                    <div class="single-feature">
                        <div class="feature-img">
                            <img src="assets/img/feature/feature-3.jpg" alt="img">
                        </div>
                        <div class="feature-content">
                            <h5>secure payments</h5>
                            <p>our financial information is protected</p>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3">
                    <div class="single-feature">
                        <div class="feature-img">
                            <img src="assets/img/feature/feature-4.jpg" alt="img">
                        </div>
                        <div class="feature-content">
                            <h5>24 hour support</h5>
                            <p>we're here to help whenever you need us.</p>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3">
                    <div class="single-feature">
                        <div class="feature-img">
                            <img src="assets/img/feature/feature-1.jpg" alt="img">
                        </div>
                        <div class="feature-content">
                            <h5>money back</h5>
                            <p>100% money back guarantee for quality</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Remove preloader
        window.addEventListener("load", () => {
            setTimeout(() => {
                document.querySelector(".preloader").classList.add("hidden");
            }, 1000);
        });

        // Update cart total display
        function updateCartDisplay() {
            const cartCounterEl = document.getElementById('cartCounter');
            const cartTotalEl = document.getElementById('cartTotal');
            const grandTotalEl = document.getElementById('grandTotal');
            const paymentTotalEl = document.getElementById('paymentTotal');
            
            let total = 0;
            let count = 0;
            
            document.querySelectorAll('tbody tr').forEach(row => {
                const subtotalEl = row.querySelector('.subtotal-amount');
                if (subtotalEl) {
                    const subtotal = parseFloat(subtotalEl.textContent.replace('৳', '').replace(',', ''));
                    total += subtotal;
                    count++;
                }
            });
            
            if (cartCounterEl) cartCounterEl.textContent = count;
            if (cartTotalEl) cartTotalEl.textContent = total.toFixed(2);
            if (grandTotalEl) grandTotalEl.textContent = total.toFixed(2);
            if (paymentTotalEl) paymentTotalEl.textContent = total.toFixed(2);
        }

        // Update quantity
        function updateQuantity(cartId, action) {
            const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
            const quantityInput = row.querySelector('.quantity-input');
            const currentQuantity = parseInt(quantityInput.value);
            const maxQuantity = parseInt(quantityInput.max);
            
            let newQuantity = currentQuantity;
            if (action === 'increase' && currentQuantity < maxQuantity) {
                newQuantity = currentQuantity + 1;
            } else if (action === 'decrease' && currentQuantity > 1) {
                newQuantity = currentQuantity - 1;
            } else {
                return;
            }
            
            // Send AJAX request to update quantity
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_quantity&cart_id=${cartId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    quantityInput.value = newQuantity;
                    
                    // Update subtotal
                    const priceEl = row.querySelector('.ms-product-price .amount');
                    const price = parseFloat(priceEl.textContent.replace('৳', '').replace(',', ''));
                    const subtotal = price * newQuantity;
                    row.querySelector('.subtotal-amount').textContent = '৳' + subtotal.toFixed(2);
                    
                    updateCartDisplay();
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error updating quantity', 'error');
            });
        }

        // Remove item
        function removeItem(cartId) {
            if (confirm('Are you sure you want to remove this item from cart?')) {
                fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove_item&cart_id=${cartId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
                        row.remove();
                        updateCartDisplay();
                        showNotification(data.message, 'success');
                        
                        // Check if cart is empty
                        if (document.querySelectorAll('tbody tr').length === 0) {
                            location.reload();
                        }
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error removing item', 'error');
                });
            }
        }

        // Empty cart
        function emptyCart() {
            if (confirm('Are you sure you want to empty your cart?')) {
                fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=empty_cart'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error emptying cart', 'error');
                });
            }
        }

        // Show payment modal
        function showPaymentModal() {
            document.getElementById('paymentModal').style.display = 'block';
        }

        // Hide payment modal
        function hidePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        // Process payment
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const cardNumber = document.getElementById('cardNumber').value;
            const cardExpiry = document.getElementById('cardExpiry').value;
            const cardCVC = document.getElementById('cardCVC').value;
            const cardName = document.getElementById('cardName').value;
            
            if (!cardNumber || !cardExpiry || !cardCVC || !cardName) {
                showNotification('Please fill all payment fields', 'error');
                return;
            }
            
            // Simulate payment processing
            const submitBtn = document.querySelector('.pay-btn');
            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;
            
            // Send payment request
            fetch('process_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `card_number=${cardNumber}&card_expiry=${cardExpiry}&card_cvc=${cardCVC}&card_name=${encodeURIComponent(cardName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hidePaymentModal();
                    document.getElementById('successModal').style.display = 'block';
                } else {
                    showNotification(data.message, 'error');
                }
                submitBtn.textContent = 'Pay Now';
                submitBtn.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Payment processing error', 'error');
                submitBtn.textContent = 'Pay Now';
                submitBtn.disabled = false;
            });
        });

        // Continue shopping after success
        function continueShopping() {
            window.location.href = 'shop.php';
        }

        // Format card number input
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // Format expiry date
        document.getElementById('cardExpiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type} show`;
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            const paymentModal = document.getElementById('paymentModal');
            const successModal = document.getElementById('successModal');
            
            if (e.target === paymentModal) {
                hidePaymentModal();
            }
            if (e.target === successModal) {
                continueShopping();
            }
        });

        // Initialize cart display
        updateCartDisplay();
    </script>
</body>
</html>