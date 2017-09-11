function Paging(table_name, num_items_in_page) {
    this.table_name = table_name;
    this.num_items_in_page = num_items_in_page;
    this.current_page = 1;
    this.total_pages = 0;
    this.created = false;

        
    this.create = function() {
        var rows = document.getElementById(table_name).rows;
        var records = (rows.length - 1); 
        this.total_pages = Math.ceil(records / num_items_in_page);
        this.created = true;
    }

    this.prev = function() {
        if (this.current_page > 1)
            this.showPage(this.current_page - 1);
    }
    
    this.next = function() {
        if (this.current_page < this.total_pages) {
            this.showPage(this.current_page + 1);
        }
    }                        
    
    this.showRecords = function(start, finish) {        
        var rows = document.getElementById(table_name).rows;
        // i starts from 1 to skip table header row
        for (var i = 1; i < rows.length; i++) {
            if ((i < start) || (i > finish)) 
                rows[i].style.display = 'none';
            else
                rows[i].style.display = '';
        }
    }
    
    this.showPage = function(page_number) {
        if (! this.created) {
            alert("pager not created");
            return;
        }
        var prev_page_anchor = document.getElementById('pg'+this.current_page);
        prev_page_anchor.className = 'pg-normal';        
        this.current_page = page_number;
        var current_page_anchor = document.getElementById('pg'+this.current_page);
        current_page_anchor.className = 'pg-selected';       
        var start = (page_number - 1) * num_items_in_page + 1;
        var finish = start + num_items_in_page - 1;
        this.showRecords(start, finish);
    }   

    this.showPageNavBar = function(pager_name, position_Id) {
        if (! this.created) {
            alert("page not created");
            return;
        }
        var element = document.getElementById(position_Id);     
        var pager_HTML = '<span onclick="' + pager_name + '.prev();" class="pg-normal"> &#171 Prev </span> | ';
        for (var page = 1; page <= this.total_pages; page++) 
            pager_HTML += '<span id="pg' + page + '" class="pg-normal" onclick="' + pager_name + '.showPage(' + page + ');">' + page + '</span> | ';
        pager_HTML += '<span onclick="'+pager_name+'.next();" class="pg-normal"> Next &#187;</span>';                   
        element.innerHTML = pager_HTML;
    }
}

