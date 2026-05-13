namespace ozon.Migrations
{
    using System;
    using System.Data.Entity.Migrations;
    
    public partial class trль : DbMigration
    {
        public override void Up()
        {
            DropColumn("dbo.Notifications", "Title");
            DropColumn("dbo.Notifications", "Message");
        }
        
        public override void Down()
        {
            AddColumn("dbo.Notifications", "Message", c => c.String());
            AddColumn("dbo.Notifications", "Title", c => c.String());
        }
    }
}
