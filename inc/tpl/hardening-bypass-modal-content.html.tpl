<style>
.sucuriscan-modal.bypass-prevention-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 500px;
    max-width: 90%;
    margin: 0;
}

.bypass-prevention-modal .sucuriscan-inline-alert-error p {
    padding: 8px;
}

.bypass-prevention-modal .sucuriscan-inline-alert-error p:last-child {
    margin-top: 0;
    margin-bottom: 8px;
}

.bypass-prevention-modal .sucuriscan-inline-alert-error strong {
    color: var(--sucuri-color-white) !important;
}

#sucuriscan-bypass-confirmation::placeholder {
    opacity: 0.3;
    color: inherit;
}

#sucuriscan-bypass-confirmation {
    width: 100%;
}
</style>

<div class="sucuriscan-overlay sucuriscan-hidden bypass-prevention-modal" data-cy="sucuriscan-modal-overlay"></div>

<div class="sucuriscan-modal sucuriscan-hidden bypass-prevention-modal" data-cy="sucuriscan-modal-container">
    <div class="sucuriscan-modal-outside">
        <div class="sucuriscan-modal-header sucuriscan-clearfix">
            <h3 class="sucuriscan-modal-title">%%SUCURI.Modal.Title%%</h3>
            <a href="#" class="sucuriscan-modal-close">&times;</a>
        </div>

        <div class="sucuriscan-modal-inside">
            <div class="sucuriscan-inline-alert-error">
                <p><strong style="color: #fff !important;">%%SUCURI.Modal.WarningLabel%%</strong> %%SUCURI.Modal.WarningText%%</p>
                <p>%%SUCURI.Modal.RevertText%%</p>
            </div>

            <p>%%SUCURI.Modal.ConfirmLabel%%</p>

            <p>
                <input type="text" id="sucuriscan-bypass-confirmation" placeholder="%%SUCURI.Modal.Placeholder%%">
            </p>

            <div class="sucuriscan-clearfix">
                <button id="sucuriscan-bypass-confirm-btn" class="button button-primary button-hero" disabled>%%SUCURI.Modal.Button%%</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        var modal = $(".bypass-prevention-modal");
        var btn = $("[name='sucuriscan_hardening_bypassPrevention']");

        btn.on("click", function (event) {
            event.preventDefault();
            modal.removeClass("sucuriscan-hidden");
        });

        /* Handle close interactions */
        $(".sucuriscan-modal-close, .sucuriscan-overlay").on("click", function (event) {
            event.preventDefault();
            modal.addClass("sucuriscan-hidden");
        });

        $(document).on("keyup", "#sucuriscan-bypass-confirmation", function (event) {
            var input = $(this).val();
            var button = $("#sucuriscan-bypass-confirm-btn");
            var isValid = (input === "ENABLE");

            if (isValid) {
                button.removeAttr("disabled");
            } else {
                button.attr("disabled", "disabled");
            }

            /* Handle Enter key */
            if (event.key === "Enter" || event.keyCode === 13) {
                if (isValid) {
                    button.click();
                }
            }
        });

        $(document).on("click", "#sucuriscan-bypass-confirm-btn", function (event) {
            event.preventDefault();
            var form = $("[name='sucuriscan_hardening_bypassPrevention']").closest("form");
            
            $("<input>")
                .attr({
                    type: "hidden",
                    name: "sucuriscan_hardening_bypassPrevention",
                    value: "Apply Hardening",
                })
                .appendTo(form);
            form.submit();
        });
    });
</script>
