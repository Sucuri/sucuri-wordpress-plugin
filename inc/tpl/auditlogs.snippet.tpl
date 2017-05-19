
%%%SUCURI.AuditLog.Date%%%

<div class="sucuriscan-clearfix sucuriscan-auditlog-entry">
    <div class="sucuriscan-pull-left sucuriscan-auditlog-entry-time">
        <span>%%SUCURI.AuditLog.Time%%</span>
    </div>

    <div class="sucuriscan-pull-left sucuriscan-auditlog-entry-event">
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="15.5px" height="18.5px" class="sucuriscan-pull-left sucuriscan-auditlog-%%SUCURI.AuditLog.Event%%"">
            <path fill-rule="evenodd" stroke="rgb(0, 0, 0)" stroke-width="1px" stroke-linecap="butt" stroke-linejoin="miter" d="M9.845,4.505 L14.481,7.098 L13.639,11.471 L8.498,11.503 L9.845,4.505 Z" />
            <path fill-rule="evenodd" stroke="rgb(0, 0, 0)" stroke-width="1px" stroke-linecap="butt" stroke-linejoin="miter" d="M3.500,1.500 L10.500,3.750 L10.500,9.375 L3.500,10.500 L3.500,1.500 Z" />
            <path class="flag-bar" fill-rule="evenodd" stroke="rgb(0, 0, 0)" stroke-width="1px" stroke-linecap="butt" stroke-linejoin="miter" fill="rgb(255, 255, 255)" d="M1.500,1.500 L3.500,1.500 L3.500,16.500 L1.500,16.500 L1.500,1.500 Z" />
        </svg>
    </div>

    <div class="sucuriscan-pull-left sucuriscan-auditlog-entry-message">
        <div class="sucuriscan-auditlog-entry-title">
            <strong>%%SUCURI.AuditLog.Username%%</strong>
            <span>%%SUCURI.AuditLog.Message%%</span>
        </div>

        <div class="sucuriscan-auditlog-entry-extra">
            %%%SUCURI.AuditLog.Extra%%%
        </div>
    </div>

    <div class="sucuriscan-pull-right sucuriscan-auditlog-entry-address">
        <span>IP: %%SUCURI.AuditLog.Address%%</span>
    </div>
</div>
