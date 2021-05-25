<ul class="prs-search-result" >
    @if(!empty($prs) && count($prs)>0)
        @foreach($prs as $p)
            <li class="ui-menu-item">
                <div id="ui-id-{{$p->id}}" onclick="select_product({{$p->id}})" tabindex="-1" class="prs-result-item">{{$p->sku .'-'. $p->name}}</div>
            </li>
        @endforeach
    @endif
</ul>