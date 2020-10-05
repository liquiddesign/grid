function gridSelectAll(table) {
    b = table.querySelectorAll("input.rowSelector");
    for (var j in b) if (b.hasOwnProperty(j)) {
        b[j].click();
    }
}