<div class="form-group">
    <label for="tags_allow_user_management" class="col-sm-2 control-label">{{ __('Allow Users to Manage Tags') }}</label>
    <div class="col-sm-6">
        <div class="onoffswitch-wrap">
            <div class="onoffswitch">
                <input type="checkbox"
                       name="settings[tags_allow_user_management]"
                       value="1"
                       id="tags_allow_user_management"
                       class="onoffswitch-checkbox"
                       @if ($allowUserManagement) checked="checked" @endif>
                <label class="onoffswitch-label" for="tags_allow_user_management"></label>
            </div>
        </div>
        <p class="form-help">{{ __('Allow non-admin users with the "Manage Tags" permission to create, edit, and delete tags.') }}</p>
    </div>
</div>
