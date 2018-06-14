<h1>$Title</h1>
<% if not $Groups %>
<p>No components by groups found.</p>
<% else %>
    <section>
        <h2>All</h2>
        <ul>
            <li>
                <p>This is to allow for simple Sketch exporting</p>
                <a href="$Link('all')">View all</a>
            </li>
        </ul>
    </section>
    <section>
        <h2>Form Kitchensink</h2>
        <ul>
            <li>
                <p>This is a form rendered by the SilverStripe backend.</p>
                <a href="$Link('formkitchensink')">View Form</a>
            </li>
        </ul>
    </section>
    <% loop $Groups %>
        <section>
            <h2>$Title</h2>
            <% if not $Items %>
                <p>No items for group "$Title" found. Do you have "ComponentName_example" files setup? Have you flushed with ?flush=all?</p>
            <% else %>
                <ul>
                    <% loop $Items %>
                        <li>
                            <a href="$Link">$Title</a>
                        </li>
                    <% end_loop %>
                </ul>
            <% end_if %>
        </section>
    <% end_loop %>
<% end_if %>
