namespace ozon.Migrations
{
    using System;
    using System.Data.Entity.Migrations;
    
    public partial class fdffdd : DbMigration
    {
        public override void Up()
        {
            CreateTable(
                "dbo.ReportHistories",
                c => new
                    {
                        Id = c.Int(nullable: false, identity: true),
                        ReportName = c.String(),
                        ReportTypeId = c.String(),
                        FileName = c.String(),
                        FilePath = c.String(),
                        FileSize = c.Long(nullable: false),
                    })
                .PrimaryKey(t => t.Id);
            
            CreateTable(
                "dbo.ReportTypes",
                c => new
                    {
                        Id = c.Int(nullable: false, identity: true),
                        ReportTypeName = c.String(),
                    })
                .PrimaryKey(t => t.Id);
            
        }
        
        public override void Down()
        {
            DropTable("dbo.ReportTypes");
            DropTable("dbo.ReportHistories");
        }
    }
}
