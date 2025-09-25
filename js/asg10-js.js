// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const phoneInput = document.getElementById('phone');
    const clearBtn = document.getElementById('clear_btn');
    const form = document.querySelector('form');
    const cartBtn = document.getElementById('cart_btn');
    const cartPanel = document.getElementById('cart_panel');
    const closeCartBtn = document.getElementById('close_cart');
    const cartItemsList = document.getElementById('cart_items');
    const cartTotal = document.getElementById('cart_total');
    
    // Cart data
    let cart = [];
    
    // Function to update cart display
    function updateCartDisplay() {
        // Clear current cart items
        cartItemsList.innerHTML = '';
        
        // Calculate total
        let total = 0;
        
        // If cart is empty, show message
        if (cart.length === 0) {
            cartItemsList.innerHTML = '<li class="empty-cart">Your cart is empty</li>';
        } else {
            // Add each item to the display
            cart.forEach((item, index) => {
                const listItem = document.createElement('li');
                
                // Create item details HTML
                let itemDetails = `
                    <div class="cart-item">
                        <div class="cart-item-details">
                            <h4>${item.name}</h4>
                            <p>`;
                
                // Add item-specific details
                if (item.type) {
                    itemDetails += `Type: ${item.type}<br>`;
                }
                
                if (item.addons && item.addons.length > 0) {
                    itemDetails += `Add-ons: ${item.addons.join(', ')}<br>`;
                }
                
                itemDetails += `Quantity: ${item.quantity}<br>
                               Price: $${item.price.toFixed(2)}<br>
                               Subtotal: $${item.subtotal.toFixed(2)}
                            </p>
                        </div>
                        <button class="remove-item-btn" data-index="${index}">×</button>
                    </div>
                `;
                
                listItem.innerHTML = itemDetails;
                cartItemsList.appendChild(listItem);
                
                // Add to total
                total += item.subtotal;
            });
            
            // Add remove item event listeners
            document.querySelectorAll('.remove-item-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    removeFromCart(index);
                });
            });
        }
        
        // Update total display
        cartTotal.textContent = total.toFixed(2);
        
        // Update cart badge if it exists
        const cartBadge = document.getElementById('cart_badge');
        if (cartBadge) {
            if (cart.length > 0) {
                cartBadge.textContent = cart.length;
                cartBadge.style.display = 'flex';
            } else {
                cartBadge.style.display = 'none';
            }
        }
    }
    
    // Function to add item to cart
    function addToCart(item) {
        cart.push(item);
        updateCartDisplay();
        
        // Show notification
        showNotification(`Added ${item.name} to cart`);
    }
    
    // Function to remove item from cart
    function removeFromCart(index) {
        if (index >= 0 && index < cart.length) {
            const removedItem = cart[index];
            cart.splice(index, 1);
            updateCartDisplay();
            
            // Show notification
            showNotification(`Removed ${removedItem.name} from cart`);
        }
    }
    
    // Function to show notification
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    // Toggle cart panel
    cartBtn.addEventListener('click', function() {
        cartPanel.classList.toggle('open');
        updateCartDisplay();
    });
    
    // Close cart panel
    closeCartBtn.addEventListener('click', function() {
        cartPanel.classList.remove('open');
    });
    
    // Add event listener for phone input to check for existing customer
    ['blur', 'change'].forEach(evt => {
        phoneInput.addEventListener(evt, function() {
            const phone = phoneInput.value;
            if (phone && phone.match(/^\d{10}$|^\d{3}-\d{3}-\d{4}$/)) {
                fetch('check_customer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'phone=' + encodeURIComponent(phone)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        // Populate form with existing customer data
                        document.getElementById('name').value = data.name;
                        document.getElementById('address').value = data.address;
                        document.getElementById('city').value = data.city;
                        document.getElementById('state').value = data.state;
                        document.getElementById('zip').value = data.zip;
                    } else {
                        // Clear form fields for new customer
                        document.getElementById('name').value = '';
                        document.getElementById('address').value = '';
                        document.getElementById('city').value = '';
                        document.getElementById('state').value = '';
                        document.getElementById('zip').value = '';
                    }
                })
                .catch(error => {
                    console.error('Error checking customer:', error);
                });
            }
        });
    });
    
    // Add event listener for clear button
    clearBtn.addEventListener('click', function() {
        // Reset the form
        form.reset();
        
        // Clear the cart
        cart = [];
        updateCartDisplay();
    });
    
    // Setup all "Add to Cart" buttons
    
    // Taco
    document.getElementById('add_taco_to_cart').addEventListener('click', function() {
        const quantity = parseInt(document.getElementById('taco_qty').value);
        if (quantity > 0) {
            const tacoType = document.getElementById('taco_type').value;
            const price = 2.00;
            
            addToCart({
                name: 'Taco',
                type: tacoType,
                quantity: quantity,
                price: price,
                subtotal: price * quantity
            });
        }
    });
    
    // Burrito
    document.getElementById('add_burritto_to_cart').addEventListener('click', function() {
        const quantity = parseInt(document.getElementById('burrito_qty').value);
        if (quantity > 0) {
            const burritoType = document.getElementById('burrito_type').value;
            const price = 4.00;
            
            addToCart({
                name: 'Burrito',
                type: burritoType,
                quantity: quantity,
                price: price,
                subtotal: price * quantity
            });
        }
    });
    
    // Nachos
    document.getElementById('add_nachos_to_cart').addEventListener('click', function() {
        const quantity = parseInt(document.getElementById('nachos_qty').value);
        if (quantity > 0) {
            const price = 5.00;
            
            // Get selected add-ons
            const addons = [];
            document.querySelectorAll('input[name="nacho_add_ons"]:checked').forEach(checkbox => {
                addons.push(checkbox.nextElementSibling.textContent);
            });
            
            addToCart({
                name: 'Nachos',
                addons: addons,
                quantity: quantity,
                price: price,
                subtotal: price * quantity
            });
        }
    });
    
    // Drink
    document.getElementById('add_drink_to_cart').addEventListener('click', function() {
        const drinkSelect = document.getElementById('drink');
        const drinkType = drinkSelect.options[drinkSelect.selectedIndex].text;
        const price = 1.00;
        
        addToCart({
            name: 'Drink',
            type: drinkType,
            quantity: 1,
            price: price,
            subtotal: price
        });
    });

    // Add event listener for form submission
    form.addEventListener('submit', function (event) {
        event.preventDefault();
    
        // Remove previous hidden cart inputs
        document.querySelectorAll('.cart-hidden').forEach(el => el.remove());
    
        cart.forEach((item, i) => {
            for (const key in item) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `cart[${i}][${key}]`;
                input.value = item[key];
                input.classList.add('cart-hidden');
                form.appendChild(input);
            }
        });
    
        const formData = new FormData(form);
    
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let summary = `✅ Order Confirmed!\n\n`;
                summary += `Confirmation Code: ${data.order_id}\n`;
                summary += `Name: ${form.name.value}\n`;
                summary += `Address: ${form.address.value}, ${form.city.value}, ${form.state.value} ${form.zip.value}\n\n`;
                summary += `🛒 Items:\n`;
                data.items.forEach(item => {
                    summary += `- ${item.quantity} x ${item.name} (${item.type}) — $${item.subtotal}\n`;
                });
                summary += `\nTotal: $${data.total}`;
                alert(summary);
            } else {
                alert(`Order failed: ${data.message}`);
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred while submitting your order.');
        });
    });
    
    // Initialize cart display
    updateCartDisplay();
    
});