<% if $LinkItems %>
    <nav>
        <ul>
            <% loop $LinkItems.Filter('ParentID', 0) %>
                <% if $IsEnabled && $Title %>
                    <li>
                        <a href="{$Link.URL}" 
                           <% if $Link.OpenInNew %>target="_blank" rel="noopener noreferrer"<% end_if %> 
                           class="menu-link">
                            {$Title}
                        </a>

                        <% if $Children %>
                            <ul class="menu-children">
                                <% loop $Children %>
                                    <% if $IsEnabled %>
                                        <li>
                                            <a href="{$Link.URL}" 
                                               <% if $Link.OpenInNew %>target="_blank" rel="noopener noreferrer"<% end_if %>>
                                                {$Title}
                                            </a>
                                        </li>
                                    <% end_if %>
                                <% end_loop %>
                            </ul>
                        <% end_if %>
                    </li>
                <% end_if %>
            <% end_loop %>
        </ul>
    </nav>
<% end_if %>