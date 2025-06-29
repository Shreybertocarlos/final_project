<div class="tab-pane fade" id="khalti4" role="tabpanel" aria-labelledby="khalti-tab4">
    <div class="card">
        <form action="{{ route('admin.khalti-settings.update') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="">Khalti Status</label>

                        <select name="khalti_status" class="form-control {{ hasError($errors, 'khalti_status') }}">
                            <option @selected(config('gatewaySettings.khalti_status') === 'active') value="active">Active</option>
                            <option @selected(config('gatewaySettings.khalti_status') === 'inactive') value="inactive">Inactive</option>
                        </select>
                        <x-input-error :messages="$errors->get('khalti_status')" class="mt-2" />
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="">Khalti Account Mode</label>
                        <select name="khalti_account_mode" class="form-control {{ hasError($errors, 'khalti_account_mode') }}">
                            <option @selected(config('gatewaySettings.khalti_account_mode') === 'sandbox') value="sandbox">Sandbox</option>
                            <option @selected(config('gatewaySettings.khalti_account_mode') === 'live') value="live">Live</option>
                        </select>
                        <x-input-error :messages="$errors->get('khalti_account_mode')" class="mt-2" />
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="">Khalti Currency Name</label>
                        <select name="khalti_currency_name" class="form-control {{ hasError($errors, 'khalti_currency_name') }}">
                            <option @selected(config('gatewaySettings.khalti_currency_name') === 'NPR') value="NPR">NPR</option>
                        </select>
                        <x-input-error :messages="$errors->get('khalti_currency_name')" class="mt-2" />
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label for="">Khalti Currency Rate (Per USD)</label>
                        <input type="text" class="form-control {{ hasError($errors, 'khalti_currency_rate') }}" name="khalti_currency_rate" value="{{ config('gatewaySettings.khalti_currency_rate') }}">
                        <x-input-error :messages="$errors->get('khalti_currency_rate')" class="mt-2" />
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label for="">Khalti Secret Key</label>
                        <input type="text" class="form-control {{ hasError($errors, 'khalti_secret_key') }}" name="khalti_secret_key" value="{{ config('gatewaySettings.khalti_secret_key') }}">
                        <x-input-error :messages="$errors->get('khalti_secret_key')" class="mt-2" />
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label for="">Khalti Public Key</label>
                        <input type="text" class="form-control {{ hasError($errors, 'khalti_public_key') }}" name="khalti_public_key" value="{{ config('gatewaySettings.khalti_public_key') }}">
                        <x-input-error :messages="$errors->get('khalti_public_key')" class="mt-2" />
                    </div>
                </div>

            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
