function gridSelectAll(checkbox, table) {
    b = table.querySelectorAll("input.rowSelector");
    for (var j in b) if (b.hasOwnProperty(j)) {
        b[j].checked = checkbox.checked;
    }
}