    </div> <!-- End Main Content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (this.dataset.isSubmitting === 'true') {
                    e.preventDefault();
                    return;
                }

                // Ensure form is valid before triggering loading state
                if (!this.checkValidity()) {
                    return;
                }

                this.dataset.isSubmitting = 'true';
                document.body.style.cursor = 'wait';
                
                const submitBtn = e.submitter || this.querySelector('button[type="submit"], input[type="submit"]');
                
                if (submitBtn) {
                    // Create hidden input to ensure button name/value is still submitted
                    if (submitBtn.name) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = submitBtn.name;
                        hiddenInput.value = submitBtn.value || '1';
                        this.appendChild(hiddenInput);
                    }
                    
                    submitBtn.disabled = true;
                    submitBtn.style.cursor = 'wait';
                    
                    if (submitBtn.tagName === 'BUTTON') {
                        submitBtn.dataset.originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Loading...';
                    } else if (submitBtn.tagName === 'INPUT') {
                        submitBtn.dataset.originalText = submitBtn.value;
                        submitBtn.value = 'Loading...';
                    }
                }
            });
        });
    });
    </script>
</body>
</html>
