<?php
require __DIR__.'/inc/common.php';
header_html('Block Test');
?>

<h1>Testing Fineprint Blocks</h1>

<h2>Conditional Blocks</h2>

[block:IfUser]
  <p>✅ User is logged in</p>
[/block:IfUser]

[block:IfGuest]
  <p>✅ User is a guest</p>
[/block:IfGuest]

[block:IfAdmin]
  <p>✅ User is admin/mod</p>
[/block:IfAdmin]

<h2>Iterator Blocks - Threads</h2>

[block:Threads]
  <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
    <h3>{ThreadTitle}</h3>
    <p>By: {ThreadAuthor} | Replies: {ReplyCount}</p>
    <a href="{ThreadURL}">View Thread</a>
  </div>
[/block:Threads]

<h2>Iterator Blocks - Categories</h2>

[block:Categories]
  <div style="display: inline-block; margin: 5px; padding: 5px 10px; background: #f0f0f0;">
    <a href="{CategoryURL}">{CategoryName}</a>
  </div>
[/block:Categories]

<h2>Nested Blocks - ThreadsByDate</h2>

[block:ThreadsByDate]
  <div style="margin: 20px 0;">
    <h3>{DateHeading}</h3>
    [block:Threads]
      <div style="padding: 5px 0;">
        → {ThreadTitle} by {ThreadAuthor}
      </div>
    [/block:Threads]
  </div>
[/block:ThreadsByDate]

<?php footer_html(); ?>
