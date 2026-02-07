<!-- Subscription Modal -->
<div class="modal fade" id="subscriptionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Manage Subscription</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.subscription.update') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="manager_id" id="sub_manager_id">
                    
                    <p>Updating subscription for: <strong id="sub_manager_name">...</strong></p>
                    <p class="text-muted small">Current Expiry: <span id="sub_current_date">...</span></p>
                    
                    <hr>

                    <div class="form-group">
                        <label class="font-weight-bold">Select Activation Period</label>
                        <select name="duration" id="duration_select" class="form-control" onchange="toggleCustomDate(this.value)">
                            <option value="1_month">Add 1 Month</option>
                            <option value="3_months">Add 3 Months</option>
                            <option value="6_months">Add 6 Months</option>
                            <option value="1_year">Add 1 Year</option>
                            <option value="" disabled>---</option>
                            <option value="custom">Set Specific Date</option>
                            <option value="deactivate" class="text-danger">Deactivate Immediately</option>
                        </select>
                    </div>

                    <div class="form-group d-none" id="custom_date_group">
                        <label>Custom Expiry Date</label>
                        <input type="date" name="custom_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openSubscriptionModal(id, name, current) {
        document.getElementById('sub_manager_id').value = id;
        document.getElementById('sub_manager_name').innerText = name;
        document.getElementById('sub_current_date').innerText = current || 'Not Active';
        $('#subscriptionModal').modal('show');
    }

    function toggleCustomDate(val) {
        if(val === 'custom') {
            document.getElementById('custom_date_group').classList.remove('d-none');
        } else {
            document.getElementById('custom_date_group').classList.add('d-none');
        }
    }
</script>