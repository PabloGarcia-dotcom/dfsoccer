document.addEventListener('DOMContentLoaded', function () {
    const itemsPerPage = 5;

    function paginate(listId, paginationId) {
        const list = document.getElementById(listId);
        const pagination = document.getElementById(paginationId);
        const items = Array.from(list.getElementsByTagName('li'));
        const totalPages = Math.ceil(items.length / itemsPerPage);
        let currentPage = 1;

        function showPage(page) {
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            items.forEach((item, index) => {
                item.style.display = (index >= start && index < end) ? 'list-item' : 'none';
            });
            pagination.innerHTML = '';
            if (totalPages > 1) {
                for (let i = 1; i <= totalPages; i++) {
                    const pageLink = document.createElement('button');
                    pageLink.textContent = i;
                    pageLink.disabled = i === page;
                    pageLink.addEventListener('click', function () {
                        currentPage = i;
                        showPage(currentPage);
                    });
                    pagination.appendChild(pageLink);
                }
            }
        }

        showPage(currentPage);
    }

    paginate('my_leagues_list', 'my_leagues_pagination');
    paginate('recent_leagues_list', 'recent_leagues_pagination');
    paginate('selected_teams_list', 'selected_teams_pagination');
});



