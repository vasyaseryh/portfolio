using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace ozon.Models
{
    public class Order
    {
        public int Id { get; set; }
        public int ProductId { get; set; }
        public int Quantity { get; set; }
        public string Address { get; set; }
        public string Status { get; set; }
        public int Price { get; set; }
        public DateTime Create { get; set; }
        public DateTime? ShipmentTime { get; set; }
        public DateTime? DeliveryTime { get; set; }
        public string location { get; set; }



    }
}
