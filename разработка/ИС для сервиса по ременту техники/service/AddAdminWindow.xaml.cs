using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;

namespace service
{
    /// <summary>
    /// Логика взаимодействия для AddAdminWindow.xaml
    /// </summary>
    public partial class AddAdminWindow : Window
    {
        public serviceEntities3 _context;

        public bool IsEdit;
        public AddAdminWindow()
        {
            InitializeComponent();
            IsEdit = false;
            _context = serviceEntities3.GetContext();
            DataContext = new Заявки();
            Клиент.ItemsSource = _context.Клиенты.ToList();
            Мастер.ItemsSource = _context.Мастера.ToList();
            Статус.ItemsSource = _context.Статусы.ToList();
        }

        public AddAdminWindow(Заявки order)
        {
            InitializeComponent();
            IsEdit = true;
            _context = serviceEntities3.GetContext();
            DataContext = order;
            Клиент.ItemsSource = _context.Клиенты.ToList();
            Мастер.ItemsSource = _context.Мастера.ToList();
            Статус.ItemsSource = _context.Статусы.ToList();
        }

        private void AddOrChange(object sender, RoutedEventArgs e)
        {
            if (IsEdit)
            {
                _context.SaveChanges();
                this.Close();
            }
            else 
            {
                Заявки order = DataContext as Заявки;
                order.Дата_создания = DateTime.Now;
                int clientId = (int)Клиент.SelectedValue;
                order.Телефон_клиента = _context.Клиенты.FirstOrDefault(el => el.id == clientId).Телефон;
                order.Email_клиента = _context.Клиенты.FirstOrDefault(el => el.id == clientId).Email;
                _context.Заявки.Add(order);
                _context.SaveChanges();
                this.Close();
            }
        }
    }
}
