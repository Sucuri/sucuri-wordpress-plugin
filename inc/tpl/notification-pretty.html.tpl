
<table class="sucuriscan-template" style="width:90%;background:#fff;font-family:Arial,Helvetica,sans-serif;border-spacing:0">
    <thead sytle="border-bottom:1px solid #ccc">
        <tr style="background-color:#4b4b4b;background-image:-moz-linear-gradient(top, #555555, #3b3b3b);background-image:-webkit-gradient(linear, 0 0, 0 100%, from(#555555), to(#3b3b3b));background-image:-webkit-linear-gradient(top, #555555, #3b3b3b);background-image:-o-linear-gradient(top, #555555, #3b3b3b);background-image:linear-gradient(to bottom, #555555, #3b3b3b);background-repeat:repeat-x">
            <td sytle="font-size:20px;font-weight:normal;color:#ffffff;padding:10px;border-right:1px solid #2f2f2f;border-left:1px solid #6f6f6f;-webkit-box-shadow:inset 0 1px 0 #888888;-moz-box-shadow:inset 0 1px 0 #888888;box-shadow:inset 0 1px 0 #888888;text-shadow:1px 1px 2px rgba(0, 0, 0, 0.5)">
                <a href="https://sucuri.net/" style="text-decoration:none;display:inline-block;margin:8px 0 5px 20px">
                    <img src="%%SUCURI.SucuriURL%%/inc/images/mainlogo.png" alt="Sucuri, Inc." style="border:none" />
                </a>
                <span style="display:inline-block;line-height:46px;margin:0 20px 0 0;float:right;color:#ffffff">%%SUCURI.TemplateTitle%%</span>
            </td>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td style="padding:20px 20px 10px 20px;border:1px solid #ccc;border-top:none">
                <h4 style="text-transform:uppercase;margin:0">Information:</h4>
                <p style="margin:0 0 10px 0">
                    Website: <a href="http://%%SUCURI.Website%%">%%SUCURI.Website%%</a><br>
                    IP Address: %%SUCURI.RemoteAddress%%<br>
                    Date/Time: %%SUCURI.Time%%<br>
                    %%SUCURI.User%%
                </p>
                <h4 style="text-transform:uppercase;margin:0">Message:</h4>
                <p style="margin:0 0 10px 0">%%%SUCURI.Message%%%</p>
            </td>
        </tr>
    </tbody>
</table>
