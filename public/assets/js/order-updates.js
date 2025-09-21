// Real-time order status updates
document.addEventListener('DOMContentLoaded', function() {
    const orderCards = document.querySelectorAll('[data-order-id]');
    const checkInterval = 30000; // Check every 30 seconds

    function updateOrderStatus(orderCard) {
        const orderId = orderCard.dataset.orderId;
        
        fetch(`api/get_order_status.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.order) {
                    const statusBadge = orderCard.querySelector('.order-status-badge');
                    const currentStatus = statusBadge.textContent.trim();

                    // Normalize casing for comparison (Title Case)
                    const serverStatus = data.order.display_status ? (data.order.display_status.charAt(0).toUpperCase() + data.order.display_status.slice(1).toLowerCase()) : '';
                    if (currentStatus !== serverStatus) {
                        // Update status badge
                        statusBadge.textContent = serverStatus;

                        // Update badge colors
                        statusBadge.className = statusBadge.className.replace(/bg-\w+-100 text-\w+-700/g, '');
                        if (serverStatus === 'Completed') {
                            statusBadge.className += ' bg-green-100 text-green-700';
                        } else if (serverStatus === 'Shipped') {
                            statusBadge.className += ' bg-blue-100 text-blue-700';
                        } else if (serverStatus === 'Cancelled') {
                            statusBadge.className += ' bg-red-100 text-red-700';
                        } else {
                            statusBadge.className += ' bg-yellow-100 text-yellow-700';
                        }

                        // Update tracking steps if they exist (new markup)
                        const tracker = orderCard.querySelector('.order-tracker');
                        if (tracker) {
                            const steps = ['Pending', 'Processing', 'Shipped', 'Completed'];
                            const currentStep = steps.indexOf(serverStatus);
                            tracker.setAttribute('data-current-step', currentStep);

                            // Update each step
                            const stepEls = tracker.querySelectorAll('.step');
                            stepEls.forEach((el) => {
                                const idx = parseInt(el.getAttribute('data-step-index'), 10);
                                const circle = el.querySelector('.step-circle');
                                const label = el.querySelector('.step-label');

                                if (idx < currentStep) {
                                    circle.classList.remove('bg-gray-100', 'text-gray-400', 'bg-blue-100', 'border-2', 'border-blue-600');
                                    circle.classList.add('bg-blue-600', 'text-white');
                                    // replace inner content with check icon
                                    if (circle.querySelector('i') === null) {
                                        circle.innerHTML = '<i data-feather="check" class="w-5 h-5"></i>';
                                    }
                                    label.classList.remove('text-gray-500');
                                    label.classList.add('text-blue-600', 'font-medium');
                                } else if (idx === currentStep) {
                                    circle.classList.remove('bg-gray-100', 'text-gray-400');
                                    circle.classList.add('bg-blue-100', 'border-2', 'border-blue-600', 'text-blue-600');
                                    // ensure the content is the step number
                                    circle.innerHTML = '<span class="font-semibold">' + (idx + 1) + '</span>';
                                    label.classList.remove('text-gray-500');
                                    label.classList.add('text-blue-600');
                                } else {
                                    circle.classList.remove('bg-blue-600', 'text-white', 'bg-blue-100', 'border-2', 'border-blue-600', 'text-blue-600');
                                    circle.classList.add('bg-gray-100', 'text-gray-400');
                                    circle.innerHTML = '<span class="font-semibold">' + (idx + 1) + '</span>';
                                    label.classList.remove('text-blue-600', 'font-medium');
                                    label.classList.add('text-gray-500');
                                }
                            });

                            // Update progress bar fills
                            const fills = tracker.querySelectorAll('.progress-bar-fill');
                            fills.forEach((fill, i) => {
                                if (i < currentStep) {
                                    fill.style.width = '100%';
                                } else if (i === currentStep) {
                                    fill.style.width = '50%';
                                } else {
                                    fill.style.width = '0%';
                                }
                            });

                            // re-run feather to render any inserted icons
                            if (window.feather) feather.replace();
                        }

                        // If order is cancelled, show cancelled message
                        if (serverStatus === 'Cancelled') {
                            const tracker = orderCard.querySelector('.order-tracker');
                            if (tracker) {
                                tracker.innerHTML = '<div class="mt-4 p-3 bg-red-50 rounded-lg text-red-700 font-medium flex items-center"><i data-feather="x-circle" class="w-5 h-5 mr-2"></i>This order was cancelled</div>';
                                if (window.feather) feather.replace();
                            }
                        }
                    }
                }
            })
            .catch(console.error);
    }

    // Initial update for each order
    orderCards.forEach(updateOrderStatus);

    // Set up periodic updates
    setInterval(() => {
        orderCards.forEach(updateOrderStatus);
    }, checkInterval);
});