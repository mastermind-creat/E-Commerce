document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('updateOrderForm');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const orderId = this.getAttribute('data-order-id');
        const statusSelect = this.querySelector('select[name="status"]');
        const status = statusSelect ? statusSelect.value : '';
        const messageElement = document.getElementById('statusMessage');
        
        if (!status) {
            messageElement.textContent = 'Please select a status';
            messageElement.className = 'mt-2 text-sm text-red-600';
            return;
        }

        if (status === 'cancelled' || status === 'completed') {
            if (!confirm(`Are you sure you want to mark this order as ${status}? This action cannot be undone.`)) {
                return;
            }
        }

    try {
            messageElement.textContent = 'Updating status...';
            messageElement.className = 'mt-2 text-sm text-gray-600';

            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', status);

            const response = await fetch('update_order_status.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Update status message
                messageElement.textContent = data.message;
                messageElement.className = 'mt-2 text-sm text-green-600';
                
                // Update status badge if it exists
                const statusBadge = document.getElementById('currentOrderStatus');
                if (statusBadge) {
                    statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    
                    // Update badge colors
                    statusBadge.className = statusBadge.className.replace(/bg-\w+-100|text-\w+-800/g, '').trim();
                    if (status === 'completed') {
                        statusBadge.className += ' bg-green-100 text-green-800';
                    } else if (status === 'pending') {
                        statusBadge.className += ' bg-yellow-100 text-yellow-800';
                    } else if (status === 'cancelled') {
                        statusBadge.className += ' bg-red-100 text-red-800';
                    } else {
                        statusBadge.className += ' bg-blue-100 text-blue-800';
                    }
                }

                // Refresh the page to show updated status
                location.reload();
            } else {
                throw new Error(data.message || 'Failed to update status');
            }
        } catch (error) {
            messageElement.textContent = error.message;
            messageElement.className = 'mt-2 text-sm text-red-600';
        }
    });
});

function resetForm() {
    const form = document.getElementById('updateOrderForm');
    const select = document.getElementById('orderStatus');
    const notes = document.getElementById('orderNotes');
    const currentStatus = select.getAttribute('data-current');
    
    select.value = currentStatus;
    notes.value = '';
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('updateOrderForm');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const orderId = this.getAttribute('data-order-id');
        const statusSelect = this.querySelector('select[name="status"]');
        const status = statusSelect ? statusSelect.value : '';
        const messageElement = document.getElementById('statusMessage');
        
        if (!status) {
            messageElement.textContent = 'Please select a status';
            messageElement.className = 'mt-2 text-sm text-red-600';
            return;
        }

        if (status === 'cancelled' || status === 'completed') {
            if (!confirm(`Are you sure you want to mark this order as ${status}? This action cannot be undone.`)) {
                return;
            }
        }

        try {
            messageElement.textContent = 'Updating status...';
            messageElement.className = 'mt-2 text-sm text-gray-600';

            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', status);

            const response = await fetch('update_order_status.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await response.json();
      if (data.success) {
        if (msg) {
          msg.textContent = data.message || 'Status updated';
          msg.className = 'mt-2 text-sm text-green-600';
        }
        const badge = container.querySelector('#currentOrderStatus');
        if (badge) {
          badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
          badge.classList.remove('bg-green-100','text-green-800','bg-yellow-100','text-yellow-800','bg-red-100','text-red-800','bg-blue-100','text-blue-800');
          if (status === 'completed') badge.classList.add('bg-green-100','text-green-800');
          else if (status === 'pending') badge.classList.add('bg-yellow-100','text-yellow-800');
          else if (status === 'cancelled') badge.classList.add('bg-red-100','text-red-800');
          else badge.classList.add('bg-blue-100','text-blue-800');
        }
      } else {
        if (msg) {
          msg.textContent = data.message || 'Failed to update status';
          msg.className = 'mt-2 text-sm text-red-600';
        }
      }
    } catch (err) {
      const msg = container.querySelector('#statusMessage');
      if (msg) {
        msg.textContent = 'Network error. Please try again.';
        msg.className = 'mt-2 text-sm text-red-600';
      }
    }
  });
});

// Auto-bind if form already on page
document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('updateOrderForm')) {
    window.initOrderManagement(document);
  }
});