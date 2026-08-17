import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {

    //  Product Bulk Actions

    const selectAll = document.getElementById('select-all');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    //update bulk actions on page load

    function updateBulkActions() {

        const checkedBoxes = document.querySelectorAll(
            '.product-checkbox:checked'
        );

        const count = checkedBoxes.length;


        // Update selected count
        if (selectedCount) {
            selectedCount.textContent = `${count} selected`;
        }


        // Show or hide bulk action bar
        if (bulkActions) {

            if (count > 0) {
                bulkActions.classList.remove('hidden');
                bulkActions.classList.add('flex');
            } else {
                bulkActions.classList.add('hidden');
                bulkActions.classList.remove('flex');
            }

        }


        // Update Select All checkbox
        if (selectAll) {

            selectAll.checked =
                count === productCheckboxes.length &&
                productCheckboxes.length > 0;

        }

    }

      //to select all the product

    selectAll?.addEventListener('change', function () {

        productCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });

        updateBulkActions();

    });
    
    //individual product selection

    productCheckboxes.forEach(checkbox => {

        checkbox.addEventListener('change', () => {
            updateBulkActions();
        });

    });

    //   bulk edit
    document
        .getElementById('bulk-edit-button')
        ?.addEventListener('click', function () {

            const checkedBoxes = document.querySelectorAll(
                '.product-checkbox:checked'
            );


            // Nothing selected
            if (checkedBoxes.length === 0) {
                return;
            }


            // Create form
            const form = document.createElement('form');

            form.method = 'GET';

            form.action = '/products/bulk-edit';


            // Add selected product IDs
            checkedBoxes.forEach(checkbox => {

                const input = document.createElement('input');

                input.type = 'hidden';

                input.name = 'selected_products[]';

                input.value = checkbox.value;

                form.appendChild(input);

            });


            // Submit form
            document.body.appendChild(form);

            form.submit();

        });

// bulk delete

    document
        .getElementById('bulk-delete-button')
        ?.addEventListener('click', function () {

            const checkedBoxes = document.querySelectorAll(
                '.product-checkbox:checked'
            );


            // Nothing selected
            if (checkedBoxes.length === 0) {
                return;
            }


            // Confirmation
            const confirmed = confirm(
                `Are you sure you want to delete ${checkedBoxes.length} selected product(s)?`
            );


            if (!confirmed) {
                return;
            }


            // Get bulk delete form
            const form = document.getElementById(
                'bulk-delete-form'
            );


            if (!form) {
                return;
            }


            // Add selected product IDs
            checkedBoxes.forEach(checkbox => {

                const input = document.createElement('input');

                input.type = 'hidden';

                input.name = 'selected_products[]';

                input.value = checkbox.value;

                form.appendChild(input);

            });


            // Submit form
            form.submit();

        });

});