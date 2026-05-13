using System;
using System.Collections.Generic;
using System.ComponentModel.DataAnnotations.Schema;
using System.Data.Entity.Infrastructure.Annotations;
using System.Linq;
using System.Reflection.Emit;
using System.Security.Policy;
using System.Text;
using System.Threading.Tasks;
using System.Data.Entity;
using System.Windows.Controls;

namespace ozon.Models
{
    public class OzonContext : DbContext
    {

        public OzonContext() : base("DefaultConnection")
        {

        }

        public DbSet<User> Users { get; set; }
        public DbSet<Product> Products { get; set; }
        public DbSet<PickupPoint> PickupPoints { get; set; }
        public DbSet<Order> Orders { get; set; }
        public DbSet<ReportHistory> ReportHistories { get; set; }
        public DbSet<ReportType> ReportTypes { get; set; }
        public DbSet<UserAction> UserActions { get; set; }
        public DbSet<ActionType> ActionTypes { get; set; }
        public DbSet<Notification> Notifications { get; set; }
        public DbSet<NotificationType> NotificationTypes { get; set; }


    }
}
