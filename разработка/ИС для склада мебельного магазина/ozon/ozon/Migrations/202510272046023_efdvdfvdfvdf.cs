namespace ozon.Migrations
{
    using System;
    using System.Data.Entity.Migrations;
    
    public partial class efdvdfvdfvdf : DbMigration
    {
        public override void Up()
        {
            AddColumn("dbo.UserActions", "UserId", c => c.Int(nullable: false));
            DropColumn("dbo.UserActions", "UserName");
        }
        
        public override void Down()
        {
            AddColumn("dbo.UserActions", "UserName", c => c.String());
            DropColumn("dbo.UserActions", "UserId");
        }
    }
}
