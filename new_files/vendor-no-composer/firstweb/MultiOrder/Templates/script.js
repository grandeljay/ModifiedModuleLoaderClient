function fwToggleSelection(element)
{
    checkboxes = document.getElementsByClassName('selectCheckbox');
    for (var i = 0; i<checkboxes.length; i++) {
        if (element.checked) {
            checkboxes[i].checked = true;
        } else {
            checkboxes[i].checked = false;
        }
    }
}
