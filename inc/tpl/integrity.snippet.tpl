
<tr>
    <th class="check-column">
        <input type="checkbox" name="sucuriscan_integrity[]" value="%%SUCURI.Integrity.StatusType%%@%%SUCURI.Integrity.FilePath%%" />
    </th>

    <td>
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="15.5px" height="18.5px" class="sucuriscan-integrity-%%SUCURI.Integrity.StatusType%%"">
            <path fill-rule="evenodd" stroke="rgb(0, 0, 0)" stroke-width="1px" stroke-linecap="butt" stroke-linejoin="miter" d="M9.845,4.505 L14.481,7.098 L13.639,11.471 L8.498,11.503 L9.845,4.505 Z" />
            <path fill-rule="evenodd" stroke="rgb(0, 0, 0)" stroke-width="1px" stroke-linecap="butt" stroke-linejoin="miter" d="M3.500,1.500 L10.500,3.750 L10.500,9.375 L3.500,10.500 L3.500,1.500 Z" />
            <path class="flag-bar" fill-rule="evenodd" stroke="rgb(0, 0, 0)" stroke-width="1px" stroke-linecap="butt" stroke-linejoin="miter" fill="rgb(255, 255, 255)" d="M1.500,1.500 L3.500,1.500 L3.500,16.500 L1.500,16.500 L1.500,1.500 Z" />
        </svg>
    </td>

    <td><span title="%%SUCURI.Integrity.FileSizeNumber%% bytes">%%SUCURI.Integrity.FileSizeHuman%%</span></td>

    <td>%%SUCURI.Integrity.ModifiedAt%%</td>

    <td>
        <span class="sucuriscan-monospace sucuriscan-wraptext">%%SUCURI.Integrity.FilePath%%</span>
        <em>%%SUCURI.Integrity.IsNotFixable%%</em>
    </td>
</tr>
